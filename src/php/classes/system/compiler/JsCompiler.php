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
		return $this->compileFileRe($path);
	}

	/**
	 * @param string $code - JS-code text
	 * @param string $path - path to file with this code if it's possible
	 * @return string
	 */
	public function compileCode($code, $path = null)
	{
		$this->dependencies = new JsCompileDependencies();
		return $this->compileCodeProcess($code, $path);
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $path
	 * @param bool $force - compile file only once or every time
	 * @return string
	 */
	private function compileFileRe($path, $force = false)
	{
		if (!file_exists($path)) return '';

		if (!$force && isset($this->allCompiledFiles[$path])) return '';

		if (preg_match('/^' . addcslashes($this->conductor->getPath(), '/') . '\//', $path)) {
			$this->compiledFiles[] = $path;
		}

		$this->allCompiledFiles[$path] = 1;
		$code = file_get_contents($path);
		$code = $this->compileCodeProcess($code, $path);
		return $code;
	}

	/**
	 * @param string $code
	 * @param string $path - need for '#lx:require'
	 * @return string
	 */
	private function compileCodeProcess($code, $path = null)
	{
		$parentDir = $path === null ? null : dirname($path) . '/';

		// Первым делом избавиться от комментариев
		$code = Minimizer::cutComments($code);

		// Удаляем директивы координации
		$code = $this->cutCoordinationDirectives($code);

		// Привести код к текущему контексту (клиент или сервер)
		$code = $this->applyContext($code);

		// Применить расширенный синтаксис
		$code = $this->sintaxExtender->applyExtendedSintax($code, $path);

		// Парсит конфиг-файлы
		if ($parentDir) {
			$code = $this->loadConfig($code, $parentDir);
		}

		// Ищет указания о подключении скриптов
		$code = $this->plugScripts($code);

		// Приведение кода к выбранному моду
		$code = $this->checkMode($code);

		// Проверка на объявление кода приватным
		list($code, $private) = $this->checkPrivate($code);

		// Компилит вызовы кода конкатенационно
		if ($parentDir) {
			$code = $this->plugAllRequires($code, $parentDir);
		}

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
	private function plugAllRequires($code, $parentDir)
	{
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

		$moduleMap = (new JsModuleMap())->getMap();
		$sitePath = \lx::$app->sitePath;
		$filePathes = [];
		foreach ($moduleNames as $moduleName) {
			if (!array_key_exists($moduleName, $moduleMap)) {
				continue;
			}

			$filePath = $moduleMap[$moduleName]['path'];
			$filePathes[] = $sitePath . '/' . $filePath;

			if (isset($moduleMap[$moduleName]['data'])) {
				$this->applyModuleData($moduleMap[$moduleName]['data'], $filePath);
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


			//TODO !!!!!!!!!!!!!!!!! Рефактоирнг компиляции - через анализатор
			/*
			проблема - выделение строк, коментов отдельно друг от друга
			последовательно разбирать текст кода - не получается, очень медленно работает
			только регулярки
			*/
			// $fa = new FileAnalyzer($fileName);


			$code = file_get_contents($fileName);

			preg_match_all('/#lx:require [\'"]?(.+)\b/', $code, $requiredFiles);
			$required = array_merge($required, $requiredFiles[1]);

			// Находим классы, которые в файле объявлены
			preg_match_all(
				'/class (.+?)\b\s*(?:extends\s+([\w\d_.]+?)\b)?\s*(?:{|#lx:namespace\s+([\w\d_]+?)\s*{)/',
				$code,
				$classes
			);
			$classes = $classes[1];
			$index = count($list);
			// Формируем карту по именам классов
			foreach ($classes as $class) {
				if (array_key_exists($class, $classesMap)) {
					if (array_key_exists($classesMap[$class], $list)) {
						\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Js-class $class is already defined from '"
								. $list[$classesMap[$class]]['path']
								. "'. It`s impossible to redeclare it from '$fileName'",
						]);
					} else {
						\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
							'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
							'msg' => "Wrong class, name: '$class'",
						]);
					}
				} else {
					$classesMap[$class] = $index;
				}
			}

			// Находим случаи наследования
			preg_match_all('/extends\s+(?:.+?\.)?(.+?)\b/', $code, $extends);

			// Формируем список инфы по файлам
			$list[] = [
				'path' => $fileName,
				'extends' => array_diff(array_unique($extends[1]), $classes),
				'depends' => [],
				'index' => $index,
				'counter' => 0
			];
		}

		// Расстановка зависимостей
		foreach ($list as &$item) {
			foreach ($item['extends'] as $extend) {
				if (!array_key_exists($extend, $classesMap)) continue;
				$list[$classesMap[$extend]]['depends'][] = $item['index'];
			}

		}
		unset($item);

		// Рекурсивное увеличение счетчика зависимостей
		$re = function ($index) use (&$re, &$list) {
			$list[$index]['counter']++;
			foreach ($list[$index]['depends'] as $depend)
				$re($depend);
		};
		foreach ($list as $item) {
			$re($item['index']);
		}

		// Сортируем файлы согласно зависимостям
		usort($list, function ($a, $b) {
			if ($a['counter'] > $b['counter']) return 1;
			if ($a['counter'] < $b['counter']) return -1;
			return 0;
		});

		// Компилим итоговый код
		$result = [];
		foreach ($list as $item) $result[] = $this->compileFileRe($item['path'], $flags->force);
		return implode('', $result);
	}

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
	private function loadConfig($code, $parentDir)
	{
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
