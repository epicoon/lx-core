<?php

namespace lx;

require_once(__DIR__ . '/SintaxExtender.php');
require_once(__DIR__ . '/Minimizer.php');

/**
 * Class JsCompiler
 * @package lx
 */
class JsCompiler
{
	const CONTEXT_CLIENT = 'client';
	const CONTEXT_SERVER = 'server';

	/** @var string */
	private $context;

	/** @var bool */
	private $buildModules;

	/** @var array */
	private $allCompiledFiles;

	/** @var array */
	private $compiledFiles;

	/** @var SintaxExtender */
	private $sintaxExtender;

	/** @var ConductorInterface */
	private $conductor;

	/** @var JsCompileDependencies */
	private $dependencies;

	/**
	 * JsCompiler constructor.
	 * @param ConductorInterface $conductor
	 */
	public function __construct($conductor = null)
	{
		$this->conductor = $conductor ?? \lx::$app->conductor;
		$this->sintaxExtender = new SintaxExtender($this);

		$this->context = self::CONTEXT_CLIENT;
		$this->buildModules = false;
		$this->allCompiledFiles = [];
		$this->compiledFiles = [];
	}

	/**
	 * @param string $context
	 */
	public function setContext($context)
	{
		if ($context != self::CONTEXT_CLIENT && $context != self::CONTEXT_SERVER) {
			return;
		}

		$this->context = $context;
	}

	/**
	 * @return string
	 */
	public function getContext()
	{
		return $this->context;
	}

	/**
	 * @param bool $value
	 */
	public function setBuildModules($value)
	{
		$this->buildModules = $value;
	}

	/**
	 * @return bool
	 */
	public function contextIsClient()
	{
		return $this->context == self::CONTEXT_CLIENT;
	}

	/**
	 * @return bool
	 */
	public function contextIsServer()
	{
		return $this->context == self::CONTEXT_SERVER;
	}

	/**
	 * @return JsCompileDependencies
	 */
	public function getDependencies()
	{
		return $this->dependencies;
	}

	/**
	 * @return array
	 */
	public function getCompiledFiles()
	{
		return $this->compiledFiles;
	}

	/**
	 * @param string $path - full path to file
	 * @return string
	 */
	public function compileFile($path)
	{
		$this->dependencies = new JsCompileDependencies();

        if (!$this->checkFileCompileAvailable($path)) {
            return '';
        }

        $this->reportFileCompiled($path);

        $code = file_get_contents($path);
        $code = $this->compileCodeInnerDirectives($code, $path);
        $code = $this->compileCodeOuterDirectives($code, $path);
        return $code;
	}

	/**
	 * @param string $code - JS-code text
	 * @param string $path - path to file with this code if it's possible
	 * @return string
	 */
	public function compileCode($code, $path = null)
	{
		$this->dependencies = new JsCompileDependencies();

		$code = $this->compileCodeInnerDirectives($code, $path);
		$code = $this->compileCodeOuterDirectives($code, $path);
		return $code;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

    /**
     * @param string $path
     * @param bool $force - compile file only once or every time
     * @return bool
     */
	private function checkFileCompileAvailable($path, $force = false)
    {
        if (!file_exists($path)) {
            return false;
        }

        if (!$force && isset($this->allCompiledFiles[$path])) {
            return false;
        }

        return true;
    }

    /**
     * @param string $path
     */
    private function reportFileCompiled($path)
    {
        if (preg_match('/^' . addcslashes($this->conductor->getPath(), '/') . '\//', $path)
            && !in_array($path, $this->compiledFiles)
        ) {
            $this->compiledFiles[] = $path;
        }
        $this->allCompiledFiles[$path] = 1;
    }

    /**
     * @param string $code
     * @param string|null $path
     * @return string
     */
	private function compileCodeInnerDirectives($code, $path = null)
    {
        // Первым делом избавиться от комментариев
        $code = Minimizer::cutComments($code);

        // Удаляем директивы координации
        $code = $this->cutCoordinationDirectives($code);

        // Разрешаем макросы
        $code = $this->processMacroses($code);

        // Привести код к текущему контексту (клиент или сервер)
        $code = $this->applyContext($code);

        // Применить расширенный синтаксис
        $code = $this->sintaxExtender->applyExtendedSintax($code, $path);

        // Парсит конфиг-файлы
        $code = $this->loadConfig($code, $path);

        // Ищет указания о подключении скриптов
        $code = $this->plugScripts($code);

        // Приведение кода к выбранному моду
        $code = $this->checkMode($code);

        return $code;
    }

    /**
     * @param string $code
     * @param string|null $path
     * @return string
     */
    private function compileCodeOuterDirectives($code, $path = null)
    {
        // Проверка на объявление кода приватным
        list($code, $private) = $this->checkPrivate($code);

        // Компилит вызовы кода конкатенационно
        $code = $this->plugAllRequires($code, $path);

        // Приватный код означает, что мы оборачиваем его в анонимную функцию
        if ($private) {
            $code = '(function(){' . $code . '})();';
        }

        $code = $this->plugAllModules($code);

        return $code;
    }

	/**
	 * Можно писать js-код под конкретный режим работы приложения при помощи директив:
	 * #lx:mode-case: SOME_MODE_0
	 *    ... some code
	 * #lx:mode-case: SOME_MODE_1
	 *    ... some code
     * #lx:mode-default:
     *    ... some code
	 * #lx:mode-end;
	 * */
	private function checkMode($code)
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

	/**
	 * Определяет объявлен ли код приватным (надо ли его обернуть в анонимную функцию)
	 * */
	private function checkPrivate($code)
	{
		$private = preg_match('/#lx:private/', $code);
		$code = preg_replace('/#lx:private;?/', '', $code);
		return [$code, $private];
	}

	/**
	 * Конкатенация кода по директиве #lx:require
	 *    Поддерживаемые конструкции:
	 *    #lx:require ClassName;
	 *    #lx:require { ClassName1, ClassName2 };
	 * */
	private function plugAllRequires($code, $path)
	{
        $parentDir = $path === null ? null : dirname($path) . '/';

		$pattern = '/(?<!\/ )(?<!\/)#lx:require(\s+-[\S]+)?\s+[\'"]?([^;]+?)[\'"]?;/';
		$code = preg_replace_callback($pattern, function ($matches) use ($parentDir) {
			$flags = $matches[1];
			$requireName = $matches[2];

			// R - флаг рекурсивного обхода подключаемого каталога
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
	 * Собирает код из перечней, указанных в директиве #lx:require
	 * */
	private function plugRequire($requireName, $parentDir, $flags)
	{
		// Формируем массив с путями ко всем подключаемым файлам
		$dirPathes = ($requireName[0] == '{')
			? preg_split('/\s*,\s*/', trim(substr($requireName, 1, -1)))
			: [$requireName];
		$filePathes = [];
		foreach ($dirPathes as $dirPath) {
			if ($dirPath{-1} != '/') {
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

	/**
	 * @param $code
	 * @return string
	 */
	private function plugAllModules($code)
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
		    if (!$moduleMap->moduleExists($moduleName)) {
		        continue;
            }

		    $path = $moduleMap->getModulePath($moduleName);
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

	/**
	 * @param $moduleData
	 */
	private function applyModuleData($moduleData, $modulePath)
	{
		$parentDir = dirname($modulePath);
		if (isset($moduleData['i18n'])) {
			$path = $moduleData['i18n'];
			$fullPath = $this->conductor->getFullPath($path, $parentDir);
			$this->dependencies->addI18n($fullPath);

			\lx::$app->useI18n($fullPath);
		}
	}

	/**
	 * Компиляция группы взаимозависимых файлов
	 * @param $fileNames - массив названий файлов (с расширением), которые надо скомпилировать
	 * @return string
	 */
	private function compileFileGroup($fileNames, $flags)
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
            if (!$this->checkFileCompileAvailable($path, $flags->force)) {
                continue;
            }

            $this->reportFileCompiled($path);

            $code = $item['code'];
            $code = $this->compileCodeOuterDirectives($code, $path);
            $result[] = $code;
		}

		return implode('', $result);
	}

    /**
     * @param string $code
     * @return string
     */
	private function processMacroses($code)
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

    /**
     * @param string $code
     * @return string
     */
	private function applyContext($code)
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

	private function cutCoordinationDirectives($code)
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

	/**
	 * Подключает скрипты, указанные в js-файлах
	 * */
	private function plugScripts($code)
	{
		$regExp = '/(?<!\/\/ )(?<!\/\/)#lx:script [\'"]?(.*?)[\'"]?;/';
		return preg_replace_callback($regExp, function ($matches) {
			$path = $matches[1];
			if (!preg_match('/\.js$/', $path)) $path .= '.js';
			$this->dependencies->addScript($path);
			return '';
		}, $code);
	}

	/**
	 * Загрузка js-данных из конфиг-файла
	 * */
	private function loadConfig($code, $path)
	{
        $parentDir = $path === null ? null : dirname($path) . '/';

		$pattern = '/(?<!\/ )(?<!\/)#lx:load\s*\(?\s*[\'"]?(.*?)[\'"]?([;,)])/';
		$code = preg_replace_callback($pattern, function ($matches) use ($parentDir) {
			$path = $matches[1];
			$fullPath = $this->conductor->getFullPath($path, $parentDir);
			$file = \lx::$app->diProcessor->createByInterface(DataFileInterface::class, [$fullPath]);
			if (!$file->exists()) {
				return 'undefined';
			}

			$data = $file->get();
			$result = ArrayHelper::arrayToJsCode($data);
			return "$result{$matches[2]}";
		}, $code);

		return $code;
	}
}
