<?php

namespace lx;

/**
 * Class Yaml
 * @package lx
 */
class Yaml
{
	/** @var string */
	private $text = '';

	/** @var array */
	private $parsed = null;

	/** @var string */
	private $referencesRootPath = null;

	/** @var array */
	private $templates = [];

	/** @var array */
	private $references = [];

	/**
	 * Yaml constructor.
	 * @param string $text
	 * @param string $referencesRootPath
	 */
	public function __construct($text = null, $referencesRootPath = null)
	{
		if ($text !== null) {
			$this->reset($text, $referencesRootPath);
		}
	}

    /**
     * @param File|string $file
     * @return array
     */
    public static function runParseFile($file)
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        if (!$file->exists()) {
            return [];
        }

        return self::runParse($file->get(), $file->getParentDirPath());
    }

    /**
     * @param string $text
     * @param string $referencesRootPath
     * @return array
     */
    public static function runParse($text = null, $referencesRootPath = null)
    {
        $instance = new self();
        return $instance->parse($text, $referencesRootPath);
    }

	/**
	 * @param string $text
	 * @param string $referencesRootPath
	 */
	public function reset($text, $referencesRootPath = null)
	{
		$this->text = $text;
		$this->parsed = null;
		$this->referencesRootPath = \lx::$app->conductor->getFullPath($referencesRootPath);
		$this->templates = [];
		$this->references = [];
	}

    /**
     * @param File|string $file
     * @return array
     */
	public function parseFile($file)
    {
        if (is_string($file)) {
            $file = new File($file);
        }

        if (!$file->exists()) {
            return [];
        }

        return $this->parse($file->get(), $file->getParentDirPath());
    }

	/**
	 * @param string $text
	 * @param string $referencesRootPath
	 * @return array
	 */
	public function parse($text = null, $referencesRootPath = null)
	{
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
	 * @param string $text
	 * @param string $referencesRootPath
	 * @return string
	 */
	public function parseToJson($text = null, $referencesRootPath = null)
	{
		return json_encode($this->parse($text, $referencesRootPath));
	}

	/**
	 * Transform YAML-text to JS-like code string
	 *
	 * @param string $text
	 * @param string $referencesRootPath
	 * @return string
	 */
	public function parseToJs($text = null, $referencesRootPath = null)
	{
		$arr = $this->parse($text, $referencesRootPath);
		return ArrayHelper::arrayToJsCode($arr);
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Local extension of YAML - multi-line commetns
	 *
	 * @param string $text
	 * @return string
	 */
	private function cutMultiLineComments($text)
	{
		$text = preg_replace('/(^|\n)###\n[\w\W]*?###(\n|$)/', '', $text);
		return $text;
	}

	/**
	 * @param string $text
	 * @return string
	 */
	private function extractReferences($text)
	{
		return preg_replace_callback('/\^ref (.*?)(\n|$)/', function ($matches) {
			$path = $matches[1];
			if (array_key_exists($path, $this->references)) {
                return "*{$matches[1]}{$matches[2]}";
            }

			$value = 'ref_error';

			$fullPath = \lx::$app->conductor->getFullPath($path, $this->referencesRootPath);
			$file = new File($fullPath);
			if (!$file->exists()) return $matches[0];

			$value = (new self($file->get(), $file->getParentDirPath()))->parse();
			$this->references[$path] = $value;

			return "*{$matches[1]}{$matches[2]}";
		}, $text);
	}

	/**
	 * Creating of basic navigation array
	 *
	 * @param string $text
	 * @return array
	 */
	private function toArray($text)
	{
		$text = preg_replace('/^\n+/', '', $text);
		$text = preg_replace('/\n+$/', '', $text);
		$arr = preg_split('/\n/', $text);

		$routerStack = [];
		$source = [];
		$concatSpacesCount = null;
		$modeConcat = 0;

		$spacesStep = $this->identifySpaceStep($text);

		$dropModeConcat = function () use ($spacesStep, &$routerStack, &$modeConcat, &$concatSpacesCount) {
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
			while ($row{$spacesCount++} == ' ') {
			}
			$row = preg_replace('/^ */', '', $row);
			if ($row == '' || $row == ']' || $row{0} == '#') continue;

			if ($modeConcat != 0) {
				if ($spacesCount != $concatSpacesCount) $dropModeConcat();
				else {
					$index = $concatSpacesCount - $spacesStep * ($modeConcat == 3 ? 2 : 1);
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
			if ($row{$len - 1} == '|' || ($len > 1 && $row{$len - 1} == '-' && $row{$len - 2} == '|')) {
				$currentSource['row'] = [preg_replace('/:[^:]+$/', ':', $row)];
				$modeConcat = 1;
				$concatSpacesCount = $spacesCount + $spacesStep;
			} elseif ($row{$len - 1} == '>' || ($len > 1 && $row{$len - 1} == '-' && $row{$len - 2} == '>')) {
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
	 * @param string $text
	 * @return int
	 */
	private function identifySpaceStep($text)
	{
		$min = INF;
		preg_match_all('/\n( +)/', $text, $matches);
		foreach ($matches[1] as $match) {
			$len = strlen($match);
			if ($len < $min) $min = $len;
		}
		return $min;
	}

	/**
	 * Process the navigation array item
	 *
	 * @param array $source
	 * @return array
	 */
	private function translateSource($source)
	{
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
	 * If the templates themselves have pointers, they will be dereferenced,
	 * but recursive links will be dereferenced only once
	 */
	private function prepareTemplates()
	{
		// If the templates themselves have links to other templates
		$this->templates = $this->applyTemplates($this->templates);

		// Links to other files are up to this point loaded and follow the logic of the templates
		$this->templates = array_merge(
			$this->templates,
			$this->references
		);
	}

	/**
	 * The applying of templates is done after the parsing process,
	 * so that links can be declared after pointers
	 *
	 * @param array $arr
	 * @return array
	 */
	private function applyTemplates($arr)
	{
		foreach ($arr as $key => $item) {
			if (is_string($item) && strlen($item) && $item{0} == '*') {
				$template = substr($item, 1);
				if (!array_key_exists($template, $this->templates)) continue;
				$arr[$key] = $this->templates[$template];
			}
			if (is_array($item)) {
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
	 * Transformation of the navigation array item source (one yaml-line as as fact) to key and value
	 *
	 * @param array $source
	 * @return array
	 */
	private function translateSourceElement($source)
	{
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
	 * @param string $text
	 * @return string
	 */
	private function rowCutComments($text)
	{
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
	 * Transformation of the navigation array item source,
	 * which is an element of the enumeration (in yaml it is a line starting with '-')
	 *
	 * @param string $sourceRow
	 * @param array $content
	 * @return array
	 */
	private function translateEnumElement($sourceRow, $content)
	{
		$key = null;
		$value = null;
		$row = preg_replace('/^-\s*/', '', $sourceRow);

		if ($row != '' && ($row{0} == '[' || $row{0} == '{')) {
			return [null, $this->translateString($row)];
		}

		preg_match_all('/((?:[\w_.!\/$\\\ -][\w\d_.!\/$\\\ -]*?)|(?:<<)):\s*(.*)/', $row, $matches);

		// For type '- value'
		if (empty($matches[0])) {
			$value = $row;
		// For type '- key: value'
		} else {
			$key = $matches[1][0];
			$value = $matches[2][0];
		}

		// If there isn't a key
		if ($key === null) {
			// If content is exist, this is a multi-line text or an array
			if (!empty($content)) {
				if ($content[0]['row']{0} == '-') {
					$value = '[';
					$temp = [];
					foreach ($content as $item) $temp[] = trim($item['row'], '- ');
					$temp = implode(',', $temp);
					if (strlen($value) > 1) $value .= ',';
					$value .= $temp . ']';
				} else {
					foreach ($content as $item) $value .= ' ' . $item['row'];
				}
			}
		// If there is a key
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
	 * Transformation of the navigation array item source,
	 * which is not an element of the enumeration
	 *
	 * @param string $row
	 * @param array $content
	 * @return array
	 */
	private function translateNotEnumElement($row, $content)
	{
		$key = null;
		$value = null;
		preg_match_all('/((?:[\w_.!\/$\\\ -][\w\d_.!\/$\\\ :-]*?)|(?:<<)):\s+(.+)/', $row, $matches);
		// For type 'value'
		if (empty($matches[0])) {
			$key = preg_replace('/:$/', '', $row);
		// For type 'key: value'
		} else {
			$key = $matches[1][0];
			$value = $matches[2][0];
		}

		// If the value doesn't exist
		if ($value === null) {
			// If the content is empty, this is an empty line
			$value = empty($content) ? '' : $this->translateSource($content);
		// If the value exists
		} else {
			// If the value starts with '&', this is a template-link
			if ($value{0} == '&') {
				$template = substr($value, 1);
				$value = $this->translateSource($content);
			// If the content exists, this is a multi-line text or an array
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
	 * @param string $template
	 * @param array $value
	 */
	private function addTemplate($template, $value)
	{
		$this->templates[$template] = $value;
	}

	/**
	 * @param string $sourceString
	 * @return array
	 */
	private function splitJsonLikeString($sourceString)
	{
		preg_match_all('/^\[\s*(.*?)\s*\]$/', $sourceString, $matches);
		if (empty($matches[1])) {
			return [];
		}

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
	 * Converting a string that came from YAML - incl. options with inline arrays and JS-like data
	 *
	 * @param string $sourceString
	 * @return array
	 */
	private function translateString($sourceString)
	{
		$str = $sourceString;
		if ($str == '') return $sourceString;

		if ($str{0} == '{' || $str{0} == '[') {
			$str = str_replace('{', '[', $str);
			$str = str_replace('}', ']', $str);
		}
		if ($str{0} != '[') return $sourceString;

		$parts = $this->splitJsonLikeString($str);
		$result = [];
		foreach ($parts as $part) {
			if (strlen($part) > 0 && $part{0} == '[') {
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
	 * Converting a true string (already without inline arrays and JS-like data), but it may contain 'key: value'
	 *
	 * @param string $value
	 * @return array
	 */
	private function translateStringDeep($value)
	{
		$arr = preg_split('/\s*,\s*/', $value);
		$result = [];
		foreach ($arr as $item) {
			if ($item == '') continue;

			$key = null;
			$val = null;
			preg_match_all('/((?:[\w_][\w\d_]*?)|(?:<<)): \s*(.+)/', $item, $matches);
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
	 * Type compliance
	 *
	 * @param mixed $val
	 * @return mixed
	 */
	private function normalizeValue($val)
	{
		if (is_array($val)) return $val;
		if (is_numeric($val)) {
			$val = (double)$val;
			if (floor($val) == $val) $val = (int)$val;
		} elseif ($val == 'true') $val = true;
		elseif ($val == 'false') $val = false;
		elseif ($val == 'null') $val = null;
		elseif (is_string($val)) {
			if (preg_match('/^!!str/', $val)) {
                $val = preg_replace('/^!!str\s*/', '', $val);
            }

			$last = mb_strlen($val) - 1;
			if ($val[0] == '"' && $val[$last] == '"') {
                $val = preg_replace('/(^"|"$)/', '', $val);
            }

            $last = mb_strlen($val) - 1;
            if ($val[0] == '\'' && $val[$last] == '\'') {
                $val = preg_replace('/(^\'|\'$)/', '', $val);
            }
		}
		return $val;
	}
}
