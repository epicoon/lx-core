<?php

namespace lx;

/**
 * Class NodeJsExecutor
 * @package lx
 */
class NodeJsExecutor {
	use ApplicationToolTrait;

    private $filePath = null;

	/**
	 * @var JsCompiler|null
	 */
    private $compiler;

	/**
	 * NodeJsExecutor constructor.
	 * @param $compiler JsCompiler
	 */
    public function __construct($compiler = null) {
		$this->compiler = $compiler ?? new JsCompiler();
	}

	/**
	 * @param $compiler JsCompiler
	 */
	public function setCompiler($compiler) {
    	$this->compiler = $compiler;
	}

	/**
	 * @return JsCompiler|null
	 */
	public function getCompiler() {
    	return $this->compiler;
	}

	/**
	 * @param $config
	 * @return bool|mixed
	 * @throws \Exception
	 */
    public function run($config) {
		if (isset($config['code'])) {
			return $this->runCode($config);
		}

		if (isset($config['file']) || isset($config['path'])) {
			return $this->runFile($config);
		}

		return false;
	}

	/**
	 * @param $path
	 * @param array $requires
	 * @param array $modules
	 * @param string $prevCode
	 * @param string $postCode
	 * @return mixed
	 * @throws \Exception
	 */
    public function runFile($path, $requires = [], $modules = [], $prevCode = '', $postCode = '') {
    	if (is_array($path)) {
    		return $this->runFile(
    			$path['file'] ?? $path['path'],
				$path['requires'] ?? [],
				$path['modules'] ?? [],
				$path['prevCode'] ?? '',
				$path['postCode'] ?? ''
			);
		} elseif (is_string($path)) {
            $this->filePath = $path;
            $code = file_get_contents($path);
            $code = $this->useJsCompiler($code, $requires, $modules, $prevCode, $postCode);
        } elseif ($path instanceof File) {
            $this->filePath = $path->getPath();
            $code = $path->get();
            $code = $this->useJsCompiler($code, $requires, $modules, $prevCode, $postCode);
        } else {
            throw new \Exception('Invalide subject for node-js executing', 400);
        }

        return $this->runCodeForce($code);
    }

	/**
	 * @param $code
	 * @param array $requires
	 * @param array $modules
	 * @param null $filePath
	 * @return mixed
	 * @throws \Exception
	 */
    public function runCode($code, $requires = [], $modules = [], $filePath = null) {
    	if (is_array($code)) {
    		return $this->runCode(
    			$code['code'],
				$code['requires'] ?? [],
				$code['modules'] ?? [],
				$code['file'] ?? $code['filePath'] ?? null
			);
		}

        $this->filePath = $filePath;
        $code = $this->useJsCompiler($code, $requires, $modules);
        return $this->runCodeForce($code);
    }

	/**
	 * @param $code
	 * @return mixed
	 * @throws \Exception
	 */
	public function runCodeForce($code) {
		$file = \lx::$conductor->getTempFile('js');
		$file->put($code);
		$command = 'node ' . $this->getExecJs() . ' "' . $file->getPath() . '"';
		$result = shell_exec($command);
		$result = preg_replace('/\s$/', '', $result);
		$result = json_decode($result, true);

		foreach ($result['log'] as $msg) {
			$this->app->log($msg['data'], $msg['category']);
		}

		foreach ($result['dump'] as $item) {
			\lx::dump($item);
		}

		if ($result['error'] != 0) {
			$this->processError($result['error'], $result['result'], $file);
		}

		$file->remove();
		return $result['result'];
	}

	/**
	 * @param $code
	 * @param array $requires
	 * @param array $modules
	 * @param string $prevCode
	 * @param string $postCode
	 * @return string
	 */
	private function useJsCompiler($code, $requires = [], $modules = [], $prevCode = '', $postCode = '') {
        $compilerContext = $this->compiler->getContext();
        $this->compiler->setContext(JsCompiler::CONTEXT_SERVER);

        $core = $this->compiler->compileCode('#lx:require @core/js/lx.js;', $this->filePath);

        $commonCode = '';
        foreach ($modules as $module) {
        	$commonCode .= '#lx:use ' . $module . ';';
		}
        
		foreach ($requires as $require) {
			$commonCode .= '#lx:require ' . $require . ';';
		}
		$commonCode .= $prevCode . $code . $postCode;

        $result = $core . $this->compiler->compileCode($commonCode, $this->filePath);

        $this->compiler->setContext($compilerContext);
        return $result;
    }

	/**
	 * @param $text
	 * @return string|string[]|null
	 */
    private function normalizeQuotes($text) {
        $result = preg_replace_callback('/\'[^\']*?"[^\']*?\'/', function($matches) {
            $str = $matches[0];
            $str = preg_replace('/"/', '#lx:dqm;', $str);
            return $str;
        }, $text);

        $regexp = '/"([^"]*?)"/';
        preg_match_all($regexp, $result, $matches);
        $result = preg_replace('/"[^"]*?"/', '№№№№', $result);
        $matches = $matches[1];
        foreach ($matches as &$str) {
            $str = preg_replace('/\'/', '\\\'', $str);
        }
        unset($str);

        $i = 0;
        $result = preg_replace_callback('/№№№№/', function($match) use ($matches, &$i) {
            $str = $matches[$i++];
            return "'$str'";
        }, $result);

        return $result;
    }

	/**
	 * @param $errorCode
	 * @param $errorData
	 * @param $file
	 * @throws \Exception
	 */
	private function processError($errorCode, $errorData, $file) {
		$msg = 'Error ';
		if ($errorCode == 1) {
			$errorCase = 'while JS-code executed';
		} elseif ($errorCode == 2) {
			$errorCase = 'while result json encoded';
		}
		$msg .= $errorCase;
		if ($this->filePath) {
			$msg .= ' for file "' . $this->filePath . '"';
		}
		$msg .= '. Name: "' . $errorData['name'] . '". Message: "' . $errorData['message'] . '".';
		//TODO $errorData['stack']

		$code = $file->get();
		$file->remove();

		$dir = new Directory(\lx::$conductor->getSystemPath('systemPath') . '/node_js_fails');
		$dir->make();
		$date = new \DateTime();
		$date = $date->format('Y-m-d');
		$ff = $dir->getFiles($date . '*.js', Directory::FIND_NAME)->getData();
		$lastFileIndex = -1;
		foreach ($ff as $name) {
			preg_match('/_(\d+?)\./', $name, $match);
			$index = (int)$match[1];
			if ($index > $lastFileIndex) $lastFileIndex = $index;
		}
		$htmlFile = new File($date . '_' . ($lastFileIndex + 1) . '.html', $dir->getPath());

		$info = '<html><head><title>Error</title></head><body><script type="text/javascript">var result=(function(){';
		$info .= PHP_EOL . PHP_EOL;
		$info .= '//========================================================================================' . PHP_EOL;
		$info .= PHP_EOL . PHP_EOL;
		$info .= '/**' . PHP_EOL;
		$info .= ' * Autogenerated js-file. Error happend ' . $errorCase . '.' . PHP_EOL;
		$info .= ' * Error name: ' . $errorData['name'] . PHP_EOL;
		$info .= ' * Error message: ' . $errorData['message'] . PHP_EOL;
		$trace = preg_split('/\s*at\s*/', $errorData['stack']);
		foreach ($trace as $i => $row) {
			if (!$i) continue;
			$info .= ' *   at ' . $row . PHP_EOL;
		}
		$info .= ' *' . PHP_EOL;
		$info .= ' * For reproduction in console use command:' . PHP_EOL;
		$info .= ' * node ' . $this->getExecJs() . ' "' . $htmlFile->getPath() . '"' . PHP_EOL;
		$info .= ' */' . PHP_EOL . PHP_EOL;

		$info .= $code . PHP_EOL . PHP_EOL;
		$info .= '//========================================================================================' . PHP_EOL;
		$info .= PHP_EOL . PHP_EOL;
		$info .= '})();console.log(result);</script></body></html>';
		$htmlFile->put($info);
		$msg .= 'Details in "' . $htmlFile->getPath() . '".';

		throw new \Exception($msg, 400);
	}

	/**
	 * Путь к скрипту, выполняющему js-код
	 * @return string
	 */
    private function getExecJs() {
        return \lx::$conductor->getSystemPath('core') . '/js/exec.js';
    }
}
