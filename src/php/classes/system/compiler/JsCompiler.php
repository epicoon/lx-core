<?php

namespace lx;

class JsCompiler
{
	const CONTEXT_CLIENT = 'client';
	const CONTEXT_SERVER = 'server';

	private static $extensionsLoaded = false;
	private static $extensions = [];

	private string $context;
	private bool $buildModules;
	private array $ignoreModules;
	private array $allCompiledFiles;
	private array $compiledFiles;
	private SintaxExtender $sintaxExtender;
	private ConductorInterface $conductor;
	private ?JsCompileDependencies $dependencies;

	public function __construct(?ConductorInterface $conductor = null)
	{
		$this->conductor = $conductor ?? \lx::$app->conductor;
		$this->sintaxExtender = new SintaxExtender($this);

		$this->context = self::CONTEXT_CLIENT;
		$this->buildModules = false;
		$this->ignoreModules = [];
		$this->allCompiledFiles = [];
		$this->compiledFiles = [];
		$this->dependencies = null;
	}

	public function setContext(string $context): void
	{
		if ($context != self::CONTEXT_CLIENT && $context != self::CONTEXT_SERVER) {
			return;
		}

		$this->context = $context;
	}

	public function getContext(): string
	{
		return $this->context;
	}

	public function setBuildModules(bool $value): void
	{
		$this->buildModules = $value;
	}

	public function ignoreModules(array $list): void
    {
        $this->ignoreModules = $list;
    }

	public function contextIsClient(): bool
	{
		return $this->context == self::CONTEXT_CLIENT;
	}

	public function contextIsServer(): bool
	{
		return $this->context == self::CONTEXT_SERVER;
	}

	public function getDependencies(): ?JsCompileDependencies
	{
		return $this->dependencies;
	}

	public function getCompiledFiles(): array
	{
		return $this->compiledFiles;
	}

	public function compileFile(string $path): string
	{
		$this->dependencies = new JsCompileDependencies();

        if (!$this->checkFileCompileAvailable($path)) {
            return '';
        }

        $this->noteFileCompiled($path);

        $code = file_get_contents($path);
        $code = $this->compileCodeInnerDirectives($code, $path);
        $code = $this->compileCodeOuterDirectives($code, $path);
        $code = $this->compileExtensions($code, $path);
        return $code;
	}

	public function compileCode(string $code, ?string $path = null): string
	{
		$this->dependencies = new JsCompileDependencies();

		$code = $this->compileCodeInnerDirectives($code, $path);
		$code = $this->compileCodeOuterDirectives($code, $path);
        $code = $this->compileExtensions($code, $path);
		return $code;
	}

    protected function compileExtensions(string $code, ?string $path = null): string
    {
        return $code;
    }

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function checkFileCompileAvailable(string $path, bool $force = false): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!$force && isset($this->allCompiledFiles[$path])) {
            return false;
        }

        return true;
    }

    private function noteFileCompiled(string $path): void
    {
        if (preg_match('/^' . addcslashes($this->conductor->getPath(), '/') . '\//', $path)
            && !in_array($path, $this->compiledFiles)
        ) {
            $this->compiledFiles[] = $path;
        }
        $this->allCompiledFiles[$path] = 1;
    }

    /**
     * @return array<JsCompilerExtensionInterafce>
     */
    private function getExtensions(): array
    {
        if (self::$extensionsLoaded === false) {
            $services = PackageBrowser::getServicesList();
            foreach ($services as $service) {
                $jsCompiler = $service->jsCompiler;
                if ($jsCompiler && $jsCompiler instanceof JsCompilerExtensionInterafce) {
                    self::$extensions[] = $jsCompiler;
                }
            }
            self::$extensionsLoaded = true;
        }

        return self::$extensions;
    }

	private function compileCodeInnerDirectives(string $code, ?string $path = null): string
    {
        $extensions = $this->getExtensions();

        foreach ($extensions as $extension) {
            $extension->setConductor($this->conductor);
            $code = $extension->beforeCutComments($code, $path);
        }

        $code = Minimizer::cutComments($code);

        foreach ($extensions as $extension) {
            $code = $extension->afterCutComments($code, $path);
        }

        $code = $this->cutCoordinationDirectives($code);
        $code = $this->processMacroses($code);
        $code = $this->applyContext($code);
        $code = $this->sintaxExtender->applyExtendedSintax($code, $path);
        $code = $this->parseYaml($code, $path);
        $code = $this->loadConfig($code, $path);
        $code = $this->plugScripts($code);
        $code = $this->checkMode($code);

        return $code;
    }

    private function compileCodeOuterDirectives(string $code, ?string $path = null): string
    {
        list($code, $private) = $this->checkPrivate($code);

        $code = $this->plugAllRequires($code, $path);

        if ($private) {
            $code = '(function(){' . $code . '})();';
        }

        $code = $this->plugAllModules($code);

        return $code;
    }

	/**
	 * You can write js-code for specific application mode by using directives:
	 * #lx:mode-case: SOME_MODE_0
	 *    ... some code
	 * #lx:mode-case: SOME_MODE_1
	 *    ... some code
     * #lx:mode-default:
     *    ... some code
	 * #lx:mode-end;
	 */
	private function checkMode(string $code): string
	{
		$mode = \lx::$app->getConfig('mode');
		$reg = '/#lx:mode-case[\w\W]*?#lx:mode-end;?/';
		$code = preg_replace_callback($reg, function ($matches) use ($mode) {
			if (!$mode) return '';

			$match = $matches[0];
			preg_match_all('/#lx:mode-case:?\s*' . $mode . '\s+([\w\W]*?)#lx:mode-/', $match, $data);
			if (empty($data[0])) {
                preg_match_all('/#lx:mode-default:\s*([\w\W]*?)#lx:mode-/', $match, $data);
                if (empty($data[0])) {
                    return '';
                }
			}

			return $data[1][0];
		}, $code);

		return $code;
	}

	private function checkPrivate(string $code): array
	{
		$private = preg_match('/#lx:private/', $code);
		$code = preg_replace('/#lx:private;?/', '', $code);
		return [$code, $private];
	}

	/**
	 * Concatenation code by directive #lx:require
	 *    Available syntax:
	 *    #lx:require ClassName;
	 *    #lx:require { ClassName1, ClassName2 };
	 */
	private function plugAllRequires(string $code, ?string $path): string
	{
        $parentDir = $path === null ? null : dirname($path) . '/';

		$pattern = '/(?<!\/ )(?<!\/)#lx:require(\s+-[\S]+)?\s+[\'"]?([^;]+?)[\'"]?;/';
		$code = preg_replace_callback($pattern, function ($matches) use ($parentDir) {
			$flags = $matches[1];
			$requireName = $matches[2];

			$flagsArr = [
				'recursive' => (strripos($flags, 'R') !== false),
				'force' => (strripos($flags, 'F') !== false),
				'test' => (strripos($flags, 'T') !== false),
			];
			return $this->plugRequire($requireName, $parentDir, DataObject::create($flagsArr));
		}, $code);

		return $code;
	}

	/**
	 * Build code by directive #lx:require
	 */
	private function plugRequire(string $requireName, ?string $parentDir, DataObject $flags): string
	{
		$dirPathes = ($requireName[0] == '{')
			? preg_split('/\s*,\s*/', trim(substr($requireName, 1, -1)))
			: [$requireName];
		$filePathes = [];
		foreach ($dirPathes as $dirPath) {
			if ($dirPath[-1] != '/') {
				if (!preg_match('/.js$/', $dirPath)) $dirPath .= '.js';
				$filePathes[] = $this->conductor->getFullPath($dirPath, $parentDir);
				continue;
			}

			$dir = new Directory($this->conductor->getFullPath($dirPath, $parentDir));
			$files = $dir->getContent([
				'mask' => '*.js',
				'findType' => Directory::FIND_NAME,
				'fullname' => true,
				'all' => $flags->recursive,
			]);
			$filePathes = array_merge($filePathes, $files->toArray());
		}

		$code = $this->compileFileGroup($filePathes, $flags);
		return $code;
	}

	private function plugAllModules(string $code): string
	{
		$pattern = '/(?<!\/ )(?<!\/)#lx:use\s+[\'"]?([^;]+?)[\'"]?;/';
		preg_match_all($pattern, $code, $matches);
		if (empty($matches[0])) {
			return $code;
		}

		$code = preg_replace($pattern, '', $code);

		$moduleNames = $matches[1];
		$this->dependencies->addModules($moduleNames);

		if (!$this->buildModules) {
			return $code;
		}

		$moduleMap = new JsModuleMap();
		$filePathes = [];
		foreach ($moduleNames as $moduleName) {
		    if (in_array($moduleName, $this->ignoreModules) || !$moduleMap->moduleExists($moduleName)) {
                //TODO зафиксировать проблему если модуль не существует
		        continue;
            }

		    $path = $moduleMap->getModulePath($moduleName);
		    if (!$path) {
		        //TODO зафиксировать проблему
		        continue;
            }
            $filePathes[] = $path;

            $data = $moduleMap->getModuleData($moduleName);
		    if (!empty($data)) {
                $this->applyModuleData($data, $path);
            }
		}

		$modulesCode = $this->compileFileGroup($filePathes, DataObject::create());
		$code = $modulesCode . $code;
		return $code;
	}

	private function applyModuleData(array $moduleData, string $modulePath): void
	{
		$parentDir = dirname($modulePath);
		if (isset($moduleData['i18n'])) {
			$path = $moduleData['i18n'];
			$fullPath = $this->conductor->getFullPath($path, $parentDir);
			$this->dependencies->addI18n($fullPath);

			\lx::$app->useI18n($fullPath);
		}
	}

	private function compileFileGroup(array $fileNames, DataObject $flags): string
	{
		// Список данных по файлам - какие классы содержатся, какие наследуются извне
		$list = [];
		// По имени класса получаем индекс инфы по файлу с этим классом в $list
		$classesMap = [];
		// Имена файлов, которые явно вызываются, соответственно должны быть убраны из $arr
		$required = [];

		foreach ($fileNames as $fileName) {
			if (!file_exists($fileName)) continue;

			$originalCode = file_get_contents($fileName);

            preg_match_all('/#lx:require [\'"]?(.+)\b/', $originalCode, $requiredFiles);
            $required = array_merge($required, $requiredFiles[1]);
            // Находим классы, которые в файле объявлены
            $reg = '/class\s+(.+?)\b\s+(?:extends\s+([\w\d_.]+?)(?:\s|{))?\s*(?:#lx:namespace\s+([\w\d_.]+?)(?:\s|{))?/';
            preg_match_all($reg, $originalCode, $matches);

            $code = $this->compileCodeInnerDirectives($originalCode, $fileName);

			$classes = $matches[1];
			$namespaces = $matches[3];
			// Формируем карту по именам классов
			foreach ($classes as $i => $class) {
			    $className = ($namespaces[$i] == '') ? $class : $namespaces[$i] . '.' . $class;
				if (array_key_exists($className, $classesMap)) {
					if (array_key_exists($classesMap[$className], $list)) {
						\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Js-class $className is already defined from '"
								. $list[$classesMap[$className]]['path']
								. "'. It`s impossible to redeclare it from '$fileName'",
						]);
					} else {
						\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Wrong class, name: '$className'",
						]);
					}
				} else {
					$classesMap[$className] = $fileName;
				}
			}

			// Находим случаи наследования
            $extends = array_diff($matches[2], ['', $fileName]);

            // Формируем список инфы по файлам
			$list[$fileName] = [
				'path' => $fileName,
                'code' => $code,
				'extends' => $extends,
				'depends' => [],
				'counter' => 0
			];
		}

		// Расстановка зависимостей
		foreach ($list as $currentClassPath => &$item) {
			foreach ($item['extends'] as $parentClassName) {
				if (!array_key_exists($parentClassName, $classesMap)) {
				    continue;
                }

                $parentClassPath = $classesMap[$parentClassName];
				if ($parentClassPath == $currentClassPath) {
				    continue;
                }

				$list[$parentClassPath]['depends'][] = $currentClassPath;
			}
		}
		unset($item);

		// Рекурсивное увеличение счетчика зависимостей
		$re = function ($index) use (&$re, &$list) {
			$list[$index]['counter']++;
			foreach ($list[$index]['depends'] as $depend) {
                $re($depend);
            }
		};
		foreach ($list as $key => $item) {
			$re($key);
		}

		// Сортируем файлы согласно зависимостям
		usort($list, function ($a, $b) {
			if ($a['counter'] > $b['counter']) return 1;
			if ($a['counter'] < $b['counter']) return -1;
			return 0;
		});

		// Компилим итоговый код
		$result = [];
		foreach ($list as $item) {
		    $path = $item['path'];
            if (!$this->checkFileCompileAvailable($path, $flags->force ?? false)) {
                continue;
            }

            $this->noteFileCompiled($path);

            $code = $item['code'];
            $code = $this->compileCodeOuterDirectives($code, $path);
            $result[] = $code;
		}

		return implode('', $result);
	}

	private function processMacroses(string $code): string
    {
        $regexp = '/(?<!\/\/ )(?<!\/\/)#lx:macros\s+(.+?)\s+(?P<re>{((?>[^{}]+)|(?P>re))*});?/';

        preg_match_all($regexp, $code, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[1] as $i => $name) {
                $text = preg_replace('/(?:^{|}$)/', '', $matches['re'][$i]);
                $code = preg_replace('/#\b' . $name . '\b/', $text, $code);
            }
        }
        
        $code = preg_replace($regexp, '', $code);
        return $code;
    }

	private function applyContext(string $code): string
	{
		$regexpTail = '\s*(?P<re>{((?>[^{}]+)|(?P>re))*});?/';
		if ($this->contextIsClient()) {
			$regexp = '/#lx:client' . $regexpTail;
			$code = preg_replace_callback($regexp, function ($match) {
				$match = $match[1];
				$match = preg_replace('/^{/', '', $match);
				$match = preg_replace('/}$/', '', $match);
				return $match;
			}, $code);

			$regexp = '/#lx:server' . $regexpTail;
			$code = preg_replace($regexp, '', $code);
		} elseif ($this->contextIsServer()) {
			$regexp = '/#lx:server' . $regexpTail;
			$code = preg_replace_callback($regexp, function ($match) {
				$match = $match[1];
				$match = preg_replace('/^{/', '', $match);
				$match = preg_replace('/}$/', '', $match);
				return $match;
			}, $code);

			$regexp = '/#lx:client' . $regexpTail;
			$code = preg_replace($regexp, '', $code);
		}
		return $code;
	}

	private function cutCoordinationDirectives(string $code): string
	{
		$regexps = [
			'/#lx:module\s+[^;]+?;/',
			'/#lx:module-data\s+{[^}]*?}/'
		];

		foreach ($regexps as $regexp) {
			$code = preg_replace($regexp, '', $code);
		}

		return $code;
	}

	private function plugScripts(string $code): string
	{
		$regExp = '/(?<!\/\/ )(?<!\/\/)#lx:script [\'"]?(.*?)[\'"]?;/';
		return preg_replace_callback($regExp, function ($matches) {
			$path = $matches[1];
			if (!preg_match('/\.js$/', $path)) $path .= '.js';
			$this->dependencies->addScript($path);
			return '';
		}, $code);
	}

	private function parseYaml(string $code, ?string $path): string
    {
        $pattern = '/#lx:yaml:(?: |\t)*+(?:\r|\n|\r\n)([\w\W]+?)#lx:yaml;/';

        $code = preg_replace_callback($pattern, function ($matches) use ($path) {
            $yaml = $matches[1];
            preg_match('/^(( |\t)*?)\S/', $yaml, $shift);
            $shift = $shift[1];
            if ($shift !== null && $shift != '') {
                $yaml = preg_replace('/(^|\r|\n|\r\n)' . $shift . '/', '$1', $yaml);
            }

            $data = Yaml::runParse($yaml, $path);
            $result = ArrayHelper::arrayToJsCode($data);
            return $result;
        }, $code);

        return $code;
    }

	private function loadConfig(string $code, ?string $path): string
	{
        $parentDir = $path === null ? null : dirname($path) . '/';

		$pattern = '/(?<!\/ )(?<!\/)#lx:load\s*\(\s*[\'"]?(.*?)[\'"]?\)/';
		$code = preg_replace_callback($pattern, function ($matches) use ($parentDir) {
			$path = $matches[1];
			$fullPath = $this->conductor->getFullPath($path, $parentDir);
			$file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath]);
			if (!$file->exists()) {
				return 'undefined';
			}

			$data = $file->get();
			$result = ArrayHelper::arrayToJsCode($data);
			return $result;
		}, $code);

		return $code;
	}
}
