<?php

// https://ru.wikipedia.org/wiki/YAML
// https://habr.com/post/270097/

namespace lx;

class Yaml {
	private $text = '';
	private $parsed = null;
	private $referencesRootPath = null;
	private $templates = [];
	private $references = [];

	public function __construct($text, $referencesRootPath = null) {
		$this->reset($text, $referencesRootPath);
	}

	/**
	 * Полностью переинициализирует текущий парсер
	 * */
	public function reset($text, $referencesRootPath = null) {
		$this->text = $text;
		$this->parsed = null;
		$this->referencesRootPath = $referencesRootPath;
		if ($this->referencesRootPath !== null && !preg_match('/\/$/', $this->referencesRootPath))
			$this->referencesRootPath .= '/';
		$this->templates = [];
		$this->references = [];
	}

	/**
	 * Преобразует yaml-текст в php-массив
	 * */
	public function parse($text = null, $referencesRootPath = null) {
		if ($text !== null) $this->reset($text, $referencesRootPath);

		if ($this->parsed !== null) return $this->parsed;

		$this->templates = [];
		$text = preg_replace('/\r\n/', chr(10), $this->text);
		$textInParsing = $this->cutMultiLineComments($text);
		$textInParsing = $this->extractReferences($textInParsing);

		$array = $this->toArray($textInParsing);
		$result = $this->translateSource($array);
		$this->prepareTemplates();
		$result = $this->applyTemplates($result);
		return $result;
	}

	/**
	 * Преобразует yaml-текст в json-текст
	 * */
	public function parseToJson($text = null, $referencesRootPath = null) {
		return json_encode($this->parse($text, $referencesRootPath));
	}

	/**
	 * Преобразует yaml-текст в формат, понятный js - такой, что его можно конкатенировать в код
	 * */
	public function parseToJs($text = null, $referencesRootPath = null) {
		$arr = $this->parse($text, $referencesRootPath);

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
					$temp[] = "$key:{$arr[$i]}";
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

		$result = $rec($arr);
		return $result;
	}

	/**
	 * Локальное расширение yaml-синтаксиса - многострочные комментарии
	 * */
	private function cutMultiLineComments($text) {
		$text = preg_replace('/(^|\n)###\n[\w\W]*?###(\n|$)/', '', $text);
		return $text;
	}

	/**
	 * Чтобы извлекать относительные ссылки нужно иметь информацию пути к каталогу
	 * */
	private function extractReferences($text) {
		return preg_replace_callback('/\^ref (.*?)(\n)/', function($matches) {
			$path = $matches[1];
			if (array_key_exists($path, $this->references))
				return "*{$matches[1]}{$matches[2]}";

			$value = 'ref_error';

			$rootPath = ($path{0} == '/')
				? \lx::sitePath()
				: $this->referencesRootPath;
			if (!$rootPath) return $matches[0];

			$fullPath = $rootPath . $path;
			$file = new File($fullPath);
			if (!$file->exists()) return $matches[0];

			$value = (new self($file->get(), $file->getParentDirPath()))->parse();
			$this->references[$path] = $value;

			return "*{$matches[1]}{$matches[2]}";
		}, $text);
	}

	/**
	 * Формирование предварительного навигационного массива
	 * */
	private function toArray($text) {
		$text = preg_replace('/^\n+/', '', $text);
		$text = preg_replace('/\n+$/', '', $text);
		$arr = preg_split('/\n/', $text);

		$routerStack = [];
		$source = [];
		$concatSpacesCount = null;
		$modeConcat = 0;

		$spacesStep = $this->identifySpaceStep($text);

		$dropModeConcat = function() use ($spacesStep, &$routerStack, &$modeConcat, &$concatSpacesCount) {
			if ($modeConcat == 1) {
				$r = $routerStack[$concatSpacesCount - $spacesStep]['row'];
				$str = array_shift($r);
				$str .= implode('\n', $r);
				$routerStack[$concatSpacesCount - $spacesStep]['row'] = $str;
			} elseif ($modeConcat == 2) {
				$r = $routerStack[$concatSpacesCount - $spacesStep]['row'];
				$str = array_shift($r);
				$str .= implode(' ', $r);
				$routerStack[$concatSpacesCount - $spacesStep]['row'] = $str;
			} elseif ($modeConcat == 3) {
				$r = $routerStack[$concatSpacesCount - $spacesStep * 2]['row'];
				$str = array_shift($r);
				$str .= '{' . implode(',', $r) . '}';
				$routerStack[$concatSpacesCount - $spacesStep * 2]['row'] = $str;
			}
			$modeConcat = 0;
			$concatSpacesCount = null;
		};

		foreach ($arr as $i => $row) {
			if ($row == '' || $row == ']' || $row{0} == '#') continue;
			$spacesCount = 0;
			while ($row{$spacesCount++} == ' ') {}
			$row = preg_replace('/^ */', '', $row);
			if ($row == '' || $row == ']' || $row{0} == '#') continue;

			if ($modeConcat != 0) {
				if ($spacesCount != $concatSpacesCount) $dropModeConcat();
				else {
					$index = $concatSpacesCount - $spacesStep * ($modeConcat==3?2:1);
					$routerStack[$index]['row'][] = $row;
					continue;
				}
			}

			if ($modeConcat == 0) {
				$currentSource = [
					'row' => $row,
					'num' => $i,
					'source' => []
				];
			}

			$len = strlen($row);
			if ($row{$len-1} == '|' || ($len > 1 && $row{$len-1} == '-' && $row{$len-2} == '|')) {
				$currentSource['row'] = [preg_replace('/:[^:]+$/', ':', $row)];
				$modeConcat = 1;
				$concatSpacesCount = $spacesCount + $spacesStep;
			} elseif ($row{$len-1} == '>' || ($len > 1 && $row{$len-1} == '-' && $row{$len-2} == '>')) {
				$currentSource['row'] = [preg_replace('/:[^:]+$/', ':', $row)];
				$modeConcat = 2;
				$concatSpacesCount = $spacesCount + $spacesStep;
			}

			if ($spacesCount == 1) {
				$source[] = $currentSource;
				$routerStack[1] = &$source[count($source) - 1];
			} else {
				if (isset($routerStack[$spacesCount - $spacesStep])) {
					$sourceBase = $routerStack[$spacesCount - $spacesStep];
					$sourceLink = &$routerStack[$spacesCount - $spacesStep]['source'];
					$x1num = $sourceBase['num'];
				} else {
					$x1num = -INF;
				}

				// Для ситуации 
				// - title:
				//     ru: 'Введение'
				//     en: 'Basic'
				if (isset($routerStack[$spacesCount - $spacesStep * 2])) {
					$sourceBaseX2 = &$routerStack[$spacesCount - $spacesStep * 2];
					if ($sourceBaseX2['num'] > $x1num && $sourceBaseX2['row']{0} == '-') {
						$sourceBaseX2['row'] = [$sourceBaseX2['row'], $currentSource['row']];
						$modeConcat = 3;
						$concatSpacesCount = $spacesCount;
						continue;
					}
				}

				// Прочие нарушения отступов игнорируем
				if (!isset($sourceLink)) continue;

				$sourceLink[] = $currentSource;
				$routerStack[$spacesCount] = &$sourceLink[count($sourceLink) - 1];
			}
		}

		if ($modeConcat != 0) $dropModeConcat();

		return $source;
	}

	/**
	 * Определение числа пробелов, разграничивающих вложенности
	 * */
	private function identifySpaceStep($text) {
		$min = INF;
		preg_match_all('/\n( +)/', $text, $matches);
		foreach ($matches[1] as $match) {
			$len = strlen($match);
			if ($len < $min) $min = $len;
		}
		return $min;
	}

	/**
	 * Преобразование элемента навигационного массива
	 * */
	private function translateSource($source) {
		if (empty($source)) return [];
		$result = [];
		foreach ($source as $value) {
			$elem = $this->translateSourceElement($value);
			$val = $this->normalizeValue($elem[1]);
			if ($elem[0] === null) $result[] = $val;
			else $result[$elem[0]] = $val;
		}
		return $result;
	}

	/**
	 * Если в самих шаблонах есть указатели, они будут разыменованы, но рекурсивные ссылки разыменуются только один раз
	 * */
	private function prepareTemplates() {
		// Если в самих шаблонах есть ссылки на другие шаблоны
		$this->templates = $this->applyTemplates($this->templates);

		// Ссылки на другие файлы к этому моменту загружены и идут по логике шаблонов
		$this->templates = array_merge(
			$this->templates,
			$this->references
		);
	}

	/**
	 * Применение шаблонов делается после процесса самого парсинга, чтобы можно было ссылки объявлять после указателей
	 * */
	private function applyTemplates($arr) {
		foreach ($arr as $key => $item) {
			if (is_string($item) && strlen($item) && $item{0} == '*') {
				$template = substr($item, 1);
				if (!array_key_exists($template, $this->templates)) continue;
				$arr[$key] = $this->templates[$template];
			} if (is_array($item)) {
				if (array_key_exists('<<', $item)) {
					$template = $item['<<'];
					if (!is_string($template) || $template{0} != '*') continue;
					$template = substr($template, 1);
					if (!array_key_exists($template, $this->templates)) continue;
					$template = $this->templates[$template];
					unset($item['<<']);
					$arr[$key] = $item + $template;
				} else {
					$arr[$key] = $this->applyTemplates($item);
				}
			}
		}

		return $arr;
	}

	/**
	 * Преобразование ресурса элемента навигационного массива (по факту - одной yaml-строки) в ключ и значение
	 * */
	private function translateSourceElement($source) {
		$content = $source['source'];
		$row = $this->rowCutComments($source['row']);

		if ($row{0} == '-') {
			list($key, $value) = $this->translateEnumElement($row, $content);
		} else {
			list($key, $value) = $this->translateNotEnumElement($row, $content);
		}

		return [$key, $value];
	}

	/**
	 * Удаление комментариев в yaml-строке
	 * */
	private function rowCutComments($text) {
		$parts = preg_split('/(?:(\'.*?\')|(".*?"))/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$result = '';
		foreach ($parts as $i => $part) {
			if (!($i % 2))
				$part = preg_replace('/\s*#.*?$/', '', $part);
			$result .= $part;
		}
		return $result;
	}

	/**
	 * Преобразование ресурса элемента навигационного массива, являющегося элементом перечисления (в yaml это строка, начинающаяся с '-')
	 * */
	private function translateEnumElement($sourceRow, $content) {
		$key = null;
		$value = null;
		$row = preg_replace('/^-\s*/', '', $sourceRow);

		if ($row{0} == '[' || $row{0} == '{') {
			return [null, $this->translateString($row)];
		}

		preg_match_all('/((?:[\w_!\/$\\\ -][\w\d_!\/$\\\ -]*?)|(?:<<)):\s*(.*)/', $row, $matches);

		// Если вида '- value'
		if (empty($matches[0])) {
			$value = $row;
		} else {
		// Если вида '- key: value'
			$key = $matches[1][0];
			$value = $matches[2][0];
		}

		// Если ключа нет
		if ($key === null) {
			// Если есть содержимое - это текст на нескольких строках
			if (!empty($content))
				foreach ($content as $item) $value .= ' ' . $item['row'];
		// С ключом
		} else {
			$content = $this->translateSource($content);
			if (is_string($value)) {
				$value = $this->translateString($value);
			}
			$content[$key] = $value;
			$value = $content;
		}

		if (is_string($value)) {
			$value = $this->translateString($value);
		}

		return [null, $value];
	}

	/**
	 * Преобразование ресурса элемента навигационного массива, не являющегося элементом перечисления
	 * */
	private function translateNotEnumElement($row, $content) {
		$key = null;
		$value = null;
		preg_match_all('/((?:[\w_!\/$\\\ -][\w\d_!\/$\\\ -]*?)|(?:<<)):\s*(.+)/', $row, $matches);
		// Если вида 'value'
		if (empty($matches[0])) {
			$key = preg_replace('/:$/', '', $row);
		} else {
		// Если вида 'key: value'
			$key = $matches[1][0];
			$value = $matches[2][0];
		}

		// Если значения нет
		if ($value === null) {
			// Если дальше нет и содержимого - это значение пустая строка
			$value = empty($content) ? '' : $this->translateSource($content);
		// Со значением
		} else {
			// Если значение начинается с '&' - это шаблон-ссылка
			if ($value{0} == '&') {
				$template = substr($value, 1);
				$value = $this->translateSource($content);
			// Если есть содержимое - это текст на нескольких строках, либо массив
			} elseif (!empty($content)) {
				if ($value{0} == '[') {
					$temp = [];
					foreach ($content as $item) $temp[] = $item['row'];
					$temp = implode(',', $temp);
					if (strlen($value) > 1) $value .= ',';
					$value .= $temp . ']';
				} else foreach ($content as $item) $value .= ' ' . $item['row'];
			}
		}

		if (is_string($value)) {
			$value = $this->translateString($value);
		}
		if (isset($template)) {			
			$this->addTemplate($template, $value);
		}
		return [$key, $value];
	}

	/**
	 * Добавление шаблона в список шаблонов парсера
	 * */
	private function addTemplate($template, $value) {
		$this->templates[$template] = $value;
	}

	/**
	 * Делим строку по внутренним запятым
	 * */
	private function splitJsonLikeString($sourceString) {
		preg_match_all('/^\[\s*(.*?)\s*\]$/', $sourceString, $matches);
		$str = $matches[1][0];

		$arr = explode(',', $str);
		$opened = 0;
		$closed = 0;
		$parts = [];
		$part = [];
		foreach ($arr as $value) {
			$opened += substr_count($value, '[');
			$closed += substr_count($value, ']');

			$part[] = $value;
			if ($opened == $closed) {
				$innerString = implode(',', $part);
				$innerString = preg_replace('/^\s*/', '', $innerString);
				$parts[] = $innerString;
				$opened = 0;
				$closed = 0;
				$part = [];
			}
		}

		return $parts;
	}

	/**
	 * Преобразование строки, пришедшей из yaml - в т.ч. вариантов с inline-массивами и js-like данными
	 * */
	private function translateString($sourceString) {
		$str = $sourceString;
		if ($str{0} == '{' || $str{0} == '[') {
			$str = str_replace('{', '[', $str);
			$str = str_replace('}', ']', $str);
		}
		if ($str{0} != '[') return $sourceString;

		$parts = $this->splitJsonLikeString($str);
		$result = [];
		foreach ($parts as $part) {
			if ($part{0} == '[') {
				$result[] = $this->translateString($part);
			} elseif (preg_match('/^\s*\b.+?\b\s*:\s*\[/', $part)) {
				preg_match_all('/^\s*(\b.+?\b)\s*:\s*(\[.*)/', $part, $matches);
				$result[$matches[1][0]] = $this->translateString($matches[2][0]);
			} else {
				$arr = $this->translateStringDeep($part);
				foreach ($arr as $key => $item) {
					if (is_string($key)) $result[$key] = $item;
					else $result[] = $item;
				}
			}
		}
		return $result;
	}

	/**
	 * Преобразование истинной строки (уже без inline-массивов и js-like данных), но она может содержать 'ключ:значение'
	 * */
	private function translateStringDeep($value) {
		$arr = preg_split('/\s*,\s*/', $value);
		$result = [];
		foreach ($arr as $item) {
			if ($item == '') continue;

			$key = null;
			$val = null;
			preg_match_all('/((?:[\w_][\w\d_]*?)|(?:<<)):\s*(.+)/', $item, $matches);
			if (empty($matches[0])) {
				$val = $item;
			} else {
				$key = $matches[1][0];
				$val = $matches[2][0];
			}

			$val = $this->normalizeValue($val);
			if ($key) {				
				$result[$key] = $val;
			} else {
				$result[] = $val;
			}
		}
		return $result;
	}

	/**
	 * Соблюдение соответствия типов
	 * */
	private function normalizeValue($val) {
		if (is_array($val)) return $val;
		if (is_numeric($val)) {
			$val = (double)$val;
			if (floor($val) == $val) $val = (int)$val;
		} elseif ($val == 'true') $val = true;
		elseif ($val == 'false') $val = false;
		elseif ($val == 'null') $val = null;
		elseif (is_string($val)) {
			if (preg_match('/^!!str/', $val))
				$val = preg_replace('/^!!str\s*/', '', $val);
			$val = preg_replace('/^[\'"]/', '', $val);
			$val = preg_replace('/[\'"]$/', '', $val);
		}
		return $val;
	}
}
