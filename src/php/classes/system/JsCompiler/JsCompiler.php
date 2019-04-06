<?php

namespace lx;

require_once(__DIR__ . '/SintaxExtender.php');
require_once(__DIR__ . '/SourcePluger.php');
require_once(__DIR__ . '/Minimizer.php');

// круто про регулярки
// https://msdn.microsoft.com/ru-ru/library/bs2twtah(v=vs.110).aspx
class JsCompiler {
	private static $filesCompiled = [];
	private static $recursiveCompiling = false;
	private static $aliases = [];

	/**
	 * Компилировать файл по его пути
	 * */
	public static function compileFile($path) {
		$code = self::compileFileRe($path);
		return self::finishPrepare($code);
	}

	/**
	 * Компилировать код, учитывая путь файла, откуда его взяли
	 * */
	public static function compileCode($code, $path) {
		$code = self::compileCodeProcess($code, $path);
		return self::finishPrepare($code);
	}

	/**
	 * Обрабатывает код в строке, который не будет фигурировать в общем исполняемом коде. Н-р код, вешаемый на обработчики событий
	 * */
	public static function compileCodeInString($str) {
		if (!self::stringIsJsCode($str)) return $str;
		$str = SintaxExtender::applyExtendedSintax($str);
		$str = self::extendCodeString($str);

		//todo!!!!!!!!!!!!!!!!
		$str = Minimizer::clearSpacesKOSTYL($str);
		$str = preg_replace('/"/', '\"', $str);

		return $str;
	}

	/**
	 * Оповещает строителя модуля и следит чтобы виджеты в случае использования других виджетов в своем коде сразу их подключили
	 * */
	public static function noteUsedWidget($widgetClass) {
		list($namespace, $name) = ClassHelper::splitClassName($widgetClass);

		$note = ModuleBuilder::noteUsedWidget(str_replace('\\', '.', $namespace), $name);
		if (!$note) return false;

		$filePath = WidgetHelper::getJsFilePath($namespace, $name);

		$file = new File($filePath);
		if ($file->exists()) {
			$code = $file->get();
			self::plugWidgets($code);
		}

		return true;
	}

	/**
	 * Преобразование php-массива в строку, которую можно вставить в js-код
	 * */
	public static function arrayToJsCode($array) {
		$rec = function($val) use (&$rec) {
			// на рекурсию
			if (is_array($val)) {
				$arr = [];
				$keys = [];
				$assoc = false;
				foreach ($val as $key => $item) {
					$keys[] = $key;
					$arr[] = $rec($item);
					if (is_string($key)) $assoc = true;
				}
				if (!$assoc) return '[' . implode(',', $arr) . ']';

				$temp = [];
				foreach ($keys as $i => $key) {
					$temp[] = "'$key':{$arr[$i]}";
				}
				return '{' . implode(',', $temp) . '}';
			}

			if (is_string($val)) {
				if ($val == '') return '\'\'';
				if ($val{0} != '\'') return "'$val'";
			}
			if ($val === true) return 'true';
			if ($val === false) return 'false';
			if ($val === null) return 'null';
			return $val;
		};

		$result = $rec($array);
		return $result;
	}

	/**
	 * С оглядкой на тип запроса будет экранирование или нет
	 * //todo - эти костыли с экранированием
	 * */
	public static function compileCodeSeeingAjax($code, $path) {
		$code = self::compileCode($code, $path);
		if (!\lx::$dialog->isAjax()) {
			$code = self::withoutAjaxModification($code);
		}
		return $code;
	}

	/**
	 * Надо экранировать экранирующие слэши
	 * //todo - для ajax экранируется где-то в другом месте, видимо - разобраться уже с этим
	 * */
	public static function withoutAjaxModification($code) {
		$code = preg_replace('/\\\\/', '\\\\\\\\\\', $code);
		return $code;		
	}

	// public
	//=========================================================================================================================
	// private

	/**
	 * Заход на рекурсивную сборку - отталкиваемся от конкретного файла
	 * */
	private static function compileFileRe($path) {
		if ( !file_exists($path) ) return '';

		if (isset(self::$filesCompiled[$path])) return '';
		self::$filesCompiled[$path] = 1;

		$code = file_get_contents($path);

		$code = self::compileCodeProcess($code, $path);
		return $code;
	}

	/**
	 * Собственно основные этапы компиляции исходного кода
	 * $path нужен для того, чтобы было от чего отталкиваться при поиске всяких #lx:require, 
	 * при этом, если путь в #lx:require начинается с '/' - $path игнорируется и файл ищется относительно корня сайта
	 * */
	private static function compileCodeProcess($code, $path) {
		$parentDir = dirname($path) . '/';

		// Первым делом избавиться от комментариев
		$code = Minimizer::cutComments($code);

		// Применить расширенный синтаксис
		$code = SintaxExtender::applyExtendedSintax($code);

		// Парсит конфиг-файлы
		$code = SourcePluger::loadConfig($code, $parentDir);

		// Ищет указания о подключении скриптов
		$code = SourcePluger::plugScripts($code);

		// Приведение кода к выбранному моду
		$code = self::checkMode($code);

		// Замена используемых превдонимов
		list ($code, $aliases) = self::checkAliases($code);
		self::$aliases = $aliases;

		// Ищет указания о подключении виджетов
		$code = self::plugWidgets($code);

		// Проверка на объявление кода приватным
		list($code, $private) = self::checkPrivate($code);

		// Компилит вызовы кода конкатенационно
		$code = self::plugAllRequires($code, $parentDir);

		// Приватный код означает, что мы оборачиваем его в анонимную функцию
		if ($private) {
			$code = '(function(){' . $code . '})();';
		}

		return $code;
	}

	/**
	 * Можно писать js-код под конкретный режим работы приложения при помощи директив:
	 * #lx:mode-case: SOME_MODE_0;
	 * 	... some code
	 * #lx:mode-case: SOME_MODE_1;
	 * 	... some code
	 * #lx:mode-end;
	 * */
	private static function checkMode($code) {
		$mode = \lx::getConfig('mode');
		$reg = '/#lx:mode-case[\w\W]*?#lx:mode-end;?/';
		$code = preg_replace_callback($reg, function($matches) use ($mode) {
			if (!$mode) return '';

			$match = $matches[0];
			preg_match_all('/#lx:mode-case:?\s*' . $mode . ';([\w\W]*?)#lx:mode-/', $match, $data);
			if (empty($data[0])) return '';

			return $data[1][0];
		}, $code);

		return $code;
	}

	/**
	 * Проверка использования замен кода вроде #lx:use test.Test as Test;
	 * Для оптимизации так можно подключать виджеты
	 * */
	private static function checkAliases($code) {
		preg_match_all('/#lx:use ([^;]*?) as ([^;]*?);/', $code, $matches);
		if (empty($matches[0])) return [$code, []];

		$aliases = [];
		foreach ($matches[0] as $i => $value) {
			$realText = $matches[1][$i];
			$alias = $matches[2][$i];

			$code = preg_replace('/(?<!#lx:require )\b' . $alias . '\b([.,;)(])/', $realText . '$1', $code);
			$code = preg_replace('/\bnew \b' . $alias . '\b/', 'new ' . $realText, $code);
			$code = preg_replace('/\bextends ' . $alias . '\b/', 'extends ' . $realText, $code);

			$realTextArray = explode('.', $realText);
			if (count($realTextArray) == 2) {
				if (!isset($aliases[$realTextArray[0]])) {
					$aliases[$realTextArray[0]] = [];
				}
				if (array_search($realTextArray[1], $aliases[$realTextArray[0]]) === false) {
					$aliases[$realTextArray[0]][] = $realTextArray[1];
				}
			} else $aliases[] = $realText;
		}

		$code = preg_replace('/#lx:use [^;]*?as[^;]*?;/', '', $code);
		return [$code, $aliases];
	}

	/*
	Положительное lookahead-условие '(?=re)'
	Соответствует, только если за ним следует регулярное выражение re.
	Отрицательное lookahead-условие '(?!re)'
	Соответствует, только если за ним не следует регулярное выражение re.
	Положительное lookbehind-условие '(?<=re)'
	Соответствует, только если перед ним следует регулярное выражение re.
	Отрицательное lookbehind-условие '(?<!re)'
	Соответствует, только если перед ним не следует регулярное выражение re.
	*/
	private static function plugWidgets($code) {
		// Находит явно указанное использование виджетов
		preg_match_all('/(?<!\/\/ )(?<!\/\/)#lx:widget ([\w\W]*?);/', $code, $widgets);
		$code = preg_replace('/(?<!\/\/ )(?<!\/\/)#lx:widget ([\w\W]*?);/', '', $code);
		foreach ($widgets[1] as $widget) {
			if ($widget == '') continue;
			$widgetArray = preg_split('/[.\\'.'\]/', $widget);

			$count = count($widgetArray);
			if ($count > 2) continue;

			if ($count == 1) {
				$namespace = 'lx';
				$widgetName = $widgetArray[0];
			} else {
				$namespace = $widgetArray[0];
				$widgetName = $widgetArray[1];
			}

			if ($widgetName{0} == '{') {
				$widgetsName = preg_split('/\s*,\s*/', trim(substr($widgetName, 1, -1)));
			} else {
				$widgetsName = [$widgetName];
			}

			foreach ($widgetsName as $name) {
				self::noteUsedWidget($namespace . '\\' . $name);
			}
		}

		// Определение используемых в коде виджетов, встроенных в платформу
		$widgetNames = WidgetHelper::getLxWidgetNames();
		foreach ($widgetNames as $name) {
			if (!empty(self::$aliases)) {
				if (isset(self::$aliases['lx']) && array_search($name, self::$aliases['lx']) !== false) {
					self::noteUsedWidget('lx\\' . $name);
					continue;
				}
			}

			$match = preg_match('/\blx\.' . $name . '\b/', $code);
			if ($match) {
				$res = self::noteUsedWidget('lx\\' . $name);
			}
		}

		// Определение используемых в коде виджетов клиентского кода
		$widgetNames = WidgetHelper::getClientWidgetNames();
		foreach ($widgetNames as $namespace => $names) {
			foreach ($names as $name) {
				if (!empty(self::$aliases)) {
					if (isset(self::$aliases[$namespace]) && array_search($name, self::$aliases[$namespace]) !== false) {
						self::noteUsedWidget($namespace . '\\' . $name);
						continue;
					}
				}

				if (
					preg_match('/new ' . $namespace . '\.' . $name . '\b/', $code)
					||
					preg_match('/\b' . $namespace . '\.' . $name . '[.,;)(]/', $code)
					||
					preg_match('/ extends ' . $namespace . '\.' . $name . '\b/', $code)
				) {
					self::noteUsedWidget($namespace . '\\' . $name);
				}
			}
		}

		return $code;
	}

	/**
	 * Определяет объявлен ли код приватным (надо ли его обернуть в анонимную функцию)
	 * */
	private static function checkPrivate($code) {
		$private = preg_match('/#lx:private/', $code);
		$code = preg_replace('/#lx:private;?/', '', $code);
		return [$code, $private];
	}

	/**
	 * Конкатенация кода по директиве #lx:require
	 *	Поддерживаемые конструкции:
	 *	#lx:require ClassName;
	 *	#lx:require { ClassName1, ClassName2 };
	 * */
	private static function plugAllRequires($code, $parentDir) {
		$pattern = '/(?<!\/ )(?<!\/)#lx:require( -.+)? [\'"]?([\w\W]*?)[\'"]?;/';
		$code = preg_replace_callback($pattern, function($matches) use ($parentDir) {
			$flags = $matches[1];
			$requireName = $matches[2];

			// R - флаг рекурсивного обхода подключаемого каталога
			self::$recursiveCompiling = (strripos($flags, 'R') !== false);
			return self::plugRequire($requireName, $parentDir);
		}, $code);

		return $code;
	}

	/**
	 * Собирает код из перечней, указанных в директиве #lx:require
	 * */
	private static function plugRequire($requireName, $parentDir) {
		// Полезно убрать пробелы
		$requireName = Minimizer::clearSpaces($requireName);

		// Формируем массив с путями ко всем подключаемым файлам
		$filePathes = ($requireName[0] == '{')
			? preg_split('/\s*,\s*/', trim(substr($requireName, 1, -1)))
			: [$requireName];
		$extractedFilePathes = [];
		foreach ($filePathes as $i => $filePath) {
			if ( $filePath{strlen($filePath) - 1} != '/' ) continue;

			// получить полный путь чтобы распарсить каталог
			$path = ($filePath{0} == '/' || $filePath{0} == '@')
				? \lx::$conductor->getFullPath($filePath)
				: $parentDir . $filePath;

			$dir = new Directory($path);

			$files = self::$recursiveCompiling
				? $dir->getAllFiles('*.js', \lx\Directory::FIND_NAME)
				: $dir->getFiles('*.js', \lx\Directory::FIND_NAME);
			$files->each(function($a) use (&$extractedFilePathes, $filePath) {
				$extractedFilePathes[] = $filePath . $a;
			});

			unset($filePathes[$i]);
		}
		$filePathes = array_merge($filePathes, $extractedFilePathes);

		//todo - если не группировать, то на следующем шаге зависимости в коде установятся между всеми файлами, не только покаталогово
		// Группируем имена файлов по каталогам
		$filesByPathes = [];
		foreach ($filePathes as $filePath) {
			//todo описать
			if ($filePath{0} == '@' || $filePath{0} == '/') {
				//todo описать
				if ($filePath{1} == '{') {
					//todo описать
					preg_match_all('/{\s*(.+?)\s*}(.+$)/', $filePath, $matches);
					$condition = $matches[1][0];
					$innerPath = $matches[2][0];
					$condition = preg_split('/\s*:\s*/', $condition);
					if ($condition[0] == 'module') {
						$module = \lx::getModule($condition[1]);
						if (!$module) continue;
						$filePath = $module->getFilePath($innerPath);
					} else {
						// Других вариантов условий пока не предусмотерно
						continue;
					}
				} else $filePath = ModuleBuilder::active()->getModule()->getFilePath($filePath);
			} else
				$filePath = $parentDir . '/' . $filePath;

			$boof = explode('/', $filePath);
			$fileName = array_pop($boof);
			if ( !preg_match('/.js$/', $fileName) ) $fileName .= '.js';
			$path = implode('/', $boof);

			if ( !preg_match('/\/$/', $path) ) $path .= '/';
			if (!array_key_exists($path, $filesByPathes)) $filesByPathes[$path] = [];
			if (!array_search($fileName, $filesByPathes[$path]))
				$filesByPathes[$path][] = $fileName;
		}

		$codes = [];
		foreach ($filesByPathes as $path => $names) {
			$codes = array_merge($codes, self::compileRequiredFromPath($names, $path));
		}

		return implode('', $codes);
	}

	/**
	 * Компиляция группы взаимозависимых файлов
	 * $arr - массив названий файлов (с расширением), которые надо скомпилировать
	 * $path - путь к директории, в которой лежат файлы, перечисленные в массиве $arr
	 * */
	private static function compileRequiredFromPath($arr, $path) {
		// Список данных по файлам - какие классы содержатся, какие наследуются извне
		$list = [];
		// По имени класса получаем индекс инфы по файлу с этим классом в $list
		$classesMap = [];
		// Имена файлов, которые явно вызываются, соответственно должны быть убраны из $arr
		$required = [];

		foreach ($arr as $name) {
			/*
			Хитрая возможность объединять в группы файлы, не находящиеся непосредственно в одном каталоге, н-р:
			directory
				- Class1
					- code.js
				- Class2
					- code.js
			Если есть зависимости между этими файлами, можно компилить так:
			#lx:require {Class1:code.js, Class2:code.js};  // из файла, который лежит непосредственно в directory
			*/
			$name = str_replace(':', '/', $name);

			$fileName = $path . $name;
			if (!file_exists($fileName)) continue;
			$code = file_get_contents($fileName);

			preg_match_all('/#lx:require [\'"]?(.+)\b/', $code, $requiredFiles);
			$required = array_merge($required, $requiredFiles[1]);

			// Находим классы, которые в файле объявлены
			preg_match_all('/class (.+?)\b\s*(extends\s+[\w\d_]+?\b)?\s*({|#lx:)/', $code, $classes);
			$classes = $classes[1];
			$index = count($list);
			// Формируем карту по именам классов
			foreach ($classes as $class) {
				if (array_key_exists($class, $classesMap)) {
					if (array_key_exists($classesMap[$class], $list))
						throw new \Exception("Js-class $class is already defined from '".$list[$classesMap[$class]]['path']."'. It`s impossible to redeclare it from '$fileName'", 1);
					else
						throw new \Exception("Wrong class, name: '$class'");
				}
				$classesMap[$class] = $index;
			}

			// Находим случаи наследования
			preg_match_all('/extends\s+(?:.+?\.)?(.+?)\b/', $code, $extends);

			// Формируем список инфы по файлам
			$list[] = [
				'name' => $name,
				'path' => $fileName,
				'extends' => array_diff(array_unique($extends[1]), $classes),
				'depends' => [],
				'index' => $index,
				'counter' => 0
			];
		}

		// Убрать из списка явно вызываемые файлы
		foreach ($list as $key => $value) {
			foreach ($required as $name) {
				$name = str_replace('/', '\/', $name);
				if (preg_match('/^'.$name.'\b/', $value['name']))
					unset($list[$key]);
			}
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
		$re = function($index) use (&$re, &$list) {
			$list[$index]['counter']++;
			foreach ($list[$index]['depends'] as $depend)
				$re($depend);
		};
		foreach ($list as $item) {
			$re($item['index']);
		}

		// Сортируем файлы согласно зависимостям
		usort($list, function($a, $b) {
			if ($a['counter'] > $b['counter']) return 1;
			if ($a['counter'] < $b['counter']) return -1;
			return 0;
		});

		// Компилим итоговый код
		$result = [];
		foreach ($list as $item) $result[] = self::compileFileRe($item['path']);
		return $result;
	}

	/**
	 * Окончательные преобразования кода уже по итогу сборки
	 * */
	private static function finishPrepare($code) {
		// Убираю ключевое слово protected перед class
		$code = str_replace('protected class', 'class', $code);

		$code = SintaxExtender::applyExtendedSintaxForClasses($code);
		return $code;
	}

	/**
	 * Если строка начинается на '(args?)=>' то считаем ее кодом js-функции
	 * */
	private static function stringIsJsCode($str) {
		return preg_match('/^\([\w\d_, ]*?\)=>/', $str);
	}

	/**
	 * Если $func использует модуль, расширит текст функции инициализацией модуля
	 * */
	private static function extendCodeString($func) {
		if (preg_match('/\bModule\b/', $func))
			$func = preg_replace('/(^\([\w\d_, ]*?\)=>)/', '$1const Module=this.getModule();', $func);
		return $func;
	}
}
