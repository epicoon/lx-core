<?php

namespace lx;

use lx;

class NodeJsExecutor implements FlightRecorderHolderInterface
{
    use FlightRecorderHolderTrait;
    
    private ?JsCompiler $compiler;
    private ?I18nMap $i18nMap = null;
    private ?string $code = null;
    private bool $withApp = false;
    private string $prevCode = '';
    private string $postCode = '';
    private ?string $filePath = null;
    private ?FileInterface $file = null;
    private array $core = [];
    private array $modules = [];
    private array $requires = [];

    public function __construct(?JsCompiler $compiler = null)
    {
		$this->compiler = $compiler ?? new JsCompiler();
	}
	
	public function reset()
    {
        $this->compiler = null;
        $this->i18nMap = null;
        $this->resetCode();
    }
    
    public function resetCode()
    {
        $this->code = null;
        $this->prevCode = '';
        $this->postCode = '';
        $this->filePath = null;
        $this->file = null;
        $this->requires = [];
        $this->modules = [];
    }

    public function getCompiler(): ?JsCompiler
    {
        return $this->compiler;
    }

	public function setCompiler(JsCompiler $compiler): NodeJsExecutor
    {
    	$this->compiler = $compiler;
    	return $this;
	}

	public function setI18nMap(I18nMap $i18nMap): NodeJsExecutor
    {
        $this->i18nMap = $i18nMap;
        return $this;
    }
    
    public function setCode(string $code): NodeJsExecutor
    {
        $this->code = $code;
        return $this;
    }

    public function configureApplication(): NodeJsExecutor
    {
        $this->withApp = true;
        return $this;
    }

    public function setPrevCode(string $code): NodeJsExecutor
    {
        $this->prevCode = $code;
        return $this;
    }

    public function setPostCode(string $code): NodeJsExecutor
    {
        $this->postCode = $code;
        return $this;
    }

    public function setPath(string $path): NodeJsExecutor
    {
        $this->filePath = $path;
        $this->file = new File($path);
        return $this;
    }

    public function setFile(FileInterface $file): NodeJsExecutor
    {
        $this->file = $file;
        $this->filePath = $file->getPath();
        return $this;
    }
    
    public function setCore(array $coreRequires): NodeJsExecutor
    {
        $this->core = $coreRequires;
        return $this;
    }

    public function setModules(array $modules): NodeJsExecutor
    {
        $this->modules = $modules;
        return $this;
    }

    public function setRequires(array $requires): NodeJsExecutor
    {
        $this->requires = $requires;
        return $this;
    }

    public function getCode(): ?string
    {
        if ($this->code) {
            return $this->code;
        }
        
        if ($this->file && $this->file->exists()) {
            $this->code = $this->file->get();
            return $this->code;
        }
        
        if ($this->filePath) {
            $file = new File($this->filePath);
            if ($file->exists()) {
                $this->file = $file;
                $this->code = $this->file->get();
                return $this->code;
            }
        }
        
        return null;
    }

    public function getFullCode(): string
    {
        $code = $this->getCode() ?? '';
        $app = '';
        if ($this->withApp) {
            $appData = CodeConverterHelper::arrayToJsCode(lx::$app->getBuildData());
            $app = "lx.app.start($appData);";
        }
        return $app . $this->prevCode . $code . $this->postCode;
    }

    /**
	 * @return mixed
	 */
    public function run()
    {
        $code = $this->getFullCode();
        if ($code == '') {
            return null;
        }
        
        if ($this->compiler) {
            $code = $this->useJsCompiler($code);
        }

        if ($this->i18nMap) {
            $code = I18nHelper::localize($code, $this->i18nMap);
        }

        return $this->runCode($code);
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function useJsCompiler(string $code): string
    {
        $compilerContext = $this->compiler->getContext();
        $this->compiler->setContext(JsCompiler::CONTEXT_SERVER);

        $core = $this->compiler->compileCode('#lx:require ' . $this->getServerAppJs() . ';', $this->filePath);
        $commonCore = '';
        foreach ($this->core as $coreItem) {
            $commonCore .= '#lx:require ' . $coreItem . ';';
        }
        if ($commonCore !== '') {
            $core .= $this->compiler->compileCode($commonCore);
        }

        $commonCode = '#lx:public;';
        foreach ($this->modules as $module) {
        	$commonCode .= '#lx:use ' . $module . ';';
		}
        
		foreach ($this->requires as $require) {
			$commonCode .= '#lx:require ' . $require . ';';
		}
		$commonCode .= $code;

        $result = $core . $this->compiler->compileCode($commonCode, $this->filePath);

        $this->compiler->setContext($compilerContext);
        return $result;
    }

    /**
     * @return mixed
     */
    private function runCode(string $code)
    {
        $file = \lx::$conductor->getTempFile('js');
        $file->put($code);
        $command = 'node ' . $this->getExecJs() . ' "' . $file->getPath() . '"';
        $result = \lx::exec($command);
        $result = preg_replace('/\s$/', '', $result);
        $result = json_decode($result, true);

        foreach ($result['log'] as $msg) {
            \lx::$app->log($msg['data'], $msg['category']);
        }

        foreach ($result['dump'] as $item) {
            \lx::dump($item);
        }

        if ($result['error'] != 0) {
            $this->processError($result['error'], $result['result'], $file);
            return false;
        }

        $file->remove();
        return $result['result'];
    }

	private function processError(int $errorCode, array $errorData, File $file): void
    {
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

		$code = $file->get();
		$file->remove();

		$dir = new Directory(\lx::$conductor->getSystemPath('node_js_fails'));
		$dir->make();
		$date = new \DateTime();
		$date = $date->format('Y-m-d');
		$ff = $dir->getFileNames($date . '*.js')->toArray();
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

        $error = '';
		$error .= ' * Error name: ' . $errorData['name'] . PHP_EOL;
        $error .= ' * Error message: ' . $errorData['message'] . PHP_EOL;
		$trace = preg_split('/\s*at\s*/', $errorData['stack']);
		foreach ($trace as $i => $row) {
			if (!$i) continue;
            $error .= ' *   at ' . $row . PHP_EOL;
		}
        $error .= ' *' . PHP_EOL;
        $this->addFlightRecord(
            'Server JS execution has failed'
            . PHP_EOL . $error
        );

        $info .= $error;
		$info .= ' * For reproduction in console use command:' . PHP_EOL;
		$info .= ' * node ' . $this->getExecJs() . ' "' . $htmlFile->getPath() . '"' . PHP_EOL;
		$info .= ' */' . PHP_EOL . PHP_EOL;

		$info .= $code . PHP_EOL . PHP_EOL;
		$info .= '//========================================================================================' . PHP_EOL;
		$info .= PHP_EOL . PHP_EOL;
		$info .= '})();console.log(result);</script></body></html>';
		$htmlFile->put($info);
		$msg .= ' Details in "' . $htmlFile->getPath() . '".';

		\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
			'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
			'msg' => $msg,
		]);
	}

    private function getExecJs(): string
    {
        return \lx::$conductor->jsNode;
    }

    private function getServerAppJs(): string
    {
        return \lx::$conductor->jsServerCore;
    }
}
