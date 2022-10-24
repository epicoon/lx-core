<?php

namespace lx;

use lx;

class JsCompiler
{
	const CONTEXT_CLIENT = 'client';
	const CONTEXT_SERVER = 'server';

	private static $extensionsLoaded = false;
	private static $extensions = [];

	private string $context;
	private bool $buildModules;
    private bool $buildModulesCss;
	private array $ignoreModules;
	private array $allCompiledFiles;
	private array $compiledFiles;
    private array $compiledModules;
	private SyntaxExtender $syntaxExtender;
	private ConductorInterface $conductor;
    private JsModuleInjectorInterface $moduleInjector;
	private ?JsCompileDependencies $dependencies;
    private array $waitingForModules;

	public function __construct(?ConductorInterface $conductor = null, ?JsModuleInjectorInterface $moduleInjector = null)
	{
		$this->conductor = $conductor ?? lx::$app->conductor;
        $this->moduleInjector = $moduleInjector ?? lx::$app->moduleInjector;
		$this->syntaxExtender = new SyntaxExtender($this);

		$this->context = self::CONTEXT_CLIENT;
		$this->buildModules = true;
        $this->buildModulesCss = true;
		$this->ignoreModules = [];
		$this->allCompiledFiles = [];
		$this->compiledFiles = [];
        $this->compiledModules = [];
		$this->dependencies = null;

        $this->waitingForModules = [];
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

    public function setBuildModulesCss(bool $value): void
    {
        $this->buildModulesCss = $value;
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
    
    public function getCompiledModules(): array
    {
        return $this->compiledModules;
    }

	public function compileFile(string $path): string
	{
        if (!$this->checkFileCompileAvailable($path)) {
            $this->dependencies = new JsCompileDependencies();
            return '';
        }

        $this->noteFileCompiled($path);
        return $this->compileCode(file_get_contents($path), $path);
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
            $services = ServiceBrowser::getServicesList();
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

        //TODO для dev-режима было бы логично оставлять комментарии. Вырезать только при минимизации.
        // Но это ломает логику работы некоторых регулярок, н-р обработка классов
        // т.к. в коментах не гарантируется корректное соответствие { и },
        // а парсинг кода класса использует рекурсивную подмаску по фигурным скобкам
        $code = Minimizer::cutComments($code);

        foreach ($extensions as $extension) {
            $code = $extension->afterCutComments($code, $path);
        }

        $code = $this->cutCoordinationDirectives($code);
        $code = $this->parseYaml($code, $path);
        $code = $this->parseMd($code, $path);
        $code = $this->processMacroses($code);
        $code = $this->applyContext($code);
        $code = $this->syntaxExtender->applyExtendedSyntax($code, $path);
        $code = $this->loadConfig($code, $path);
        $code = $this->plugScripts($code);
        $code = $this->checkMode($code);

        return $code;
    }

    private function compileCodeOuterDirectives(string $code, ?string $path = null): string
    {
        $code = $this->markDev($code, $path);
        $code = $this->checkPublic($code);
        $code = $this->plugAllRequires($code, $path);
        $code = $this->plugAllModules($code, $path);
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
		$mode = lx::$app->getConfig('mode');
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
    
    private function checkPublic(string $code): string
    {
        $publicObjects = [];
        $code = preg_replace_callback(
            '/#lx:public\s+((?:class|function)\s+(\b.+?\b))/',
            function ($matches) use (&$publicObjects) {
                $publicObjects[] = $matches[2];
                return $matches[1];
            },
            $code
        );
        foreach ($publicObjects as $object) {
            $code .= "lx.globalContext.$object=$object;";
        }

        if (preg_match('/#lx:public;/', $code)) {
            $code = preg_replace('/#lx:public;/', '', $code);
        } else {
            $code = '(function(){' . $code . '})();';
        }

        return $code;
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
		$code = preg_replace_callback($pattern, function ($matches) use ($parentDir, $path) {
			$flags = $matches[1];
			$requireName = $matches[2];

			$flagsArr = [
				'recursive' => (strripos($flags, 'R') !== false),
				'force' => (strripos($flags, 'F') !== false),
				'test' => (strripos($flags, 'T') !== false),
			];
			return $this->plugRequire($requireName, DataObject::create($flagsArr), $parentDir, $path);
		}, $code);

		return $code;
	}

	/**
	 * Build code by directive #lx:require
	 */
	private function plugRequire(string $requireName, DataObject $flags, ?string $parentDir, ?string $rootPath): string
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

		$code = $this->compileFileGroup($filePathes, $flags, $rootPath);
		return $code;
	}

	private function plugAllModules(string $code, ?string $rootPath): string
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

		$filePathes = [];
        $modulesForBuild = [];
		foreach ($moduleNames as $moduleName) {
            $this->checkModule($moduleName, $modulesForBuild, $filePathes);
		}

        if (!$this->buildModulesCss || lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_NONE)) {
            $preseted = [];
        } else {
            lx::$app->events->trigger(JsModulesComponent::EVENT_BEFORE_GET_CSS_ASSETS, [
                'moduleNames' => $modulesForBuild,
            ]);
            $preseted = lx::$app->jsModules->getPresetedCssClasses($modulesForBuild);
        }

		$modulesCode = $this->compileFileGroup($filePathes, DataObject::create(), $rootPath);
        if (!empty($preseted)) {
            foreach ($preseted as &$item) {
                $item = "'$item'";
            }
            unset($item);
            $modulesCode .= 'lx.onReady(()=>lx.app.cssManager.registerPreseted([' . implode(',', $preseted) . ']));';
        }
		$code = $modulesCode . $code;
        foreach ($modulesForBuild as $name) {
            if (!in_array($name, $this->compiledModules)) {
                $this->compiledModules[] = $name;
            }
        }
		return $code;
	}

    private function checkModuleDependencies(string $modulePath, array &$modulesForBuild, array &$filePathes): void
    {
        $pattern = '/(?<!\/ )(?<!\/)#lx:use\s+[\'"]?([^;]+?)[\'"]?;/';
        $code = file_get_contents($modulePath);
        preg_match_all($pattern, $code, $matches);
        if (empty($matches[0])) {
            return;
        }
        $moduleNames = $matches[1];
        foreach ($moduleNames as $moduleName) {
            $this->checkModule($moduleName, $modulesForBuild, $filePathes);
        }
    }

    private function checkModule(string $moduleName, array &$modulesForBuild, array &$filePathes): void
    {
        $moduleName = $this->moduleInjector->resolveModuleName($moduleName);
        if (in_array($moduleName, $this->ignoreModules) || in_array($moduleName, $modulesForBuild)) {
            return;
        }

        lx::$app->events->trigger(JsModulesComponent::EVENT_BEFORE_COMPILE_MODULE_CODE, [
            'moduleName' => $moduleName,
        ]);

        $moduleInfo = lx::$app->jsModules->getModuleInfo($moduleName);
        if (!$moduleInfo) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                'msg' => "Module '$moduleName' doesn't exist",
            ]);
            return;
        }
        $path = $moduleInfo->getPath();
        if (!$path) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                'msg' => "File path for '$moduleName' can not be found",
            ]);
            return;
        }
        if (!file_exists($path)) {
            \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                'msg' => "Module '$moduleName' has path '$path', but this file doesn't exist",
            ]);
            return;
        }

        if (in_array($path, $filePathes)) {
            return;
        }

        $filePathes[] = $path;
        if ($moduleInfo->hasMetaData()) {
            $this->applyModuleMetaData($moduleInfo);
        }
        $modulesForBuild[] = $moduleName;
        $this->checkModuleDependencies($path, $modulesForBuild, $filePathes);
    }

	private function applyModuleMetaData(JsModuleInfo $moduleInfo): void
	{
		if ($moduleInfo->hasMetaData('i18n')) {
			$path = $moduleInfo->getMetaData('i18n');
            $parentDir = dirname($moduleInfo->getPath());
			$fullPath = $this->conductor->getFullPath($path, $parentDir);
			$this->dependencies->addI18n($fullPath);
			lx::$app->useI18n($fullPath);
		}
	}

	private function compileFileGroup(array $fileNames, DataObject $flags, ?string $rootPath): string
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
            $reg = '/(?:#lx:namespace\s+([\w\d_.]+?)\s*;\s*)?class\s+(.+?)\b\s+(?:extends\s+([\w\d_.]+?)(?:\s|{))?/';
            preg_match_all($reg, $originalCode, $matches);

            $code = $this->compileCodeInnerDirectives($originalCode, $fileName);

            $namespaces = $matches[1];
			$classes = $matches[2];
			// Формируем карту по именам классов
			foreach ($classes as $i => $class) {
			    $className = ($namespaces[$i] == '') ? $class : $namespaces[$i] . '.' . $class;
				if (array_key_exists($className, $classesMap)) {
					if (array_key_exists($classesMap[$className], $list)) {
						lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Js-class $className is already defined from '"
								. $list[$classesMap[$className]]['path']
								. "'. It`s impossible to redeclare it from '$fileName'",
						]);
					} else {
						lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Wrong class, name: '$className'",
						]);
					}
				} else {
					$classesMap[$className] = $fileName;
				}
			}

			// Находим случаи наследования
            $dependsOf = array_diff($matches[3], ['', $fileName]);

            // Находим использование модулей
            $pattern = '/(?<!\/ )(?<!\/)#lx:use\s+[\'"]?([^;]+?)[\'"]?;/';
            preg_match_all($pattern, $originalCode, $matches);
            if (!empty($matches[0])) {
                $dependsOf = array_values(array_unique(array_merge($dependsOf, $matches[1])));
            }

            // Формируем список инфы по файлам
			$list[$fileName] = [
				'path' => $fileName,
                'code' => $code,
				'dependsOf' => $dependsOf,
				'dependencies' => [],
				'counter' => 0
			];
		}

		// Расстановка зависимостей
		foreach ($list as $currentClassPath => &$item) {
			foreach ($item['dependsOf'] as $parentClassName) {
				if (!array_key_exists($parentClassName, $classesMap)) {
				    continue;
                }

                $parentClassPath = $classesMap[$parentClassName];
				if ($parentClassPath == $currentClassPath) {
				    continue;
                }

                if (!in_array($currentClassPath, $list[$parentClassPath]['dependencies'])) {
                    $list[$parentClassPath]['dependencies'][] = $currentClassPath;
                }
			}
		}
		unset($item);

		// Рекурсивное увеличение счетчика зависимостей
		$re = function ($index) use (&$re, &$list) {
			$list[$index]['counter']++;
			foreach ($list[$index]['dependencies'] as $depend) {
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
            $code = $this->markDevInterrupting($code, $rootPath);
            $result[] = $code;
		}

		return implode('', $result);
	}

	private function processMacroses(string $code, ?string $macrosesSrc = null): string
    {
        $needRemove = false;
        if ($macrosesSrc === null) {
            $macrosesSrc = $code;
            $needRemove = true;
        }
        $regexp = '/(?<!\/\/ )(?<!\/\/)#lx:macros\s+(.+?)\s+(?P<re>{((?>[^{}]+)|(?P>re))*});?/';

        preg_match_all($regexp, $macrosesSrc, $matches);
        if (!empty($matches[0])) {
            foreach ($matches[1] as $i => $name) {
                $text = preg_replace('/(?:^{|}$)/', '', $matches['re'][$i]);
                $code = preg_replace('/>>>\b' . $name . '\b/', $text, $code);
            }
        }

        if ($needRemove) {
            $code = preg_replace($regexp, '', $code);
        }
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
            $result = CodeConverterHelper::arrayToJsCode($data);
            $result = $this->processMacroses($result, $yaml);
            return $result;
        }, $code);

        return $code;
    }

    private function parseMd(string $code, ?string $path): string
    {
        $parentDir = $path === null ? null : dirname($path) . '/';

        $pattern = '/(?<!\/ )(?<!\/)#lx:md\s*\(\s*[\'"]?(.*?)[\'"]?\)/';
        $code = preg_replace_callback($pattern, function ($matches) use ($parentDir) {
            $path = $matches[1];
            if (!preg_match('/\.md$/', $path)) {
                $path .= '.md';
            }
            $fullPath = $this->conductor->getFullPath($path, $parentDir);
            $file = new File($fullPath);
            if (!$file->exists()) {
                return '""';
            }

            $converter = new MdConverter();
            $result = $converter->setFile($file)->run();
            $result = addcslashes($result, '"');
            $result = '"' . $result . '"';

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
			$file = lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath]);
			if (!$file->exists()) {
				return 'undefined';
			}

			$data = $file->get();
			$result = CodeConverterHelper::arrayToJsCode($data);
			return $result;
		}, $code);

		return $code;
	}

    private function markDev(string $code, ?string $path): string
    {
        if (!lx::$app->isMode(lx::MODE_DEV) || $path === null) {
            return $code;
        }

        return PHP_EOL
            . "/* @lx-begin-js-file: $path */"
            . PHP_EOL . $code . PHP_EOL
            . "/* @lx-end-js-file: $path */"
            . PHP_EOL;
    }

    private function markDevInterrupting(string $code, ?string $path): string
    {
        if (!lx::$app->isMode(lx::MODE_DEV) || $path === null) {
            return $code;
        }

        return PHP_EOL
            . "/* @lx-interrupted-js-file: $path */"
            . PHP_EOL . $code . PHP_EOL
            . "/* @lx-continue-js-file: $path */"
            . PHP_EOL;
    }
}
