<?php

namespace lx;

/**
 * Class StringHelper
 * @package lx
 */
class StringHelper
{
	public static function camelToSnake(string $string): string
	{
		return lcfirst(preg_replace_callback('/(.)([A-Z])/', function ($match) {
			return $match[1] . '_' . strtolower($match[2]);
		}, $string));
	}

    /**
     * @param string $string
     * @param string|array $delimiter
     * @return string
     */
	public static function snakeToCamel(string $string, $delimiter = '_'): string
	{
		if (is_array($delimiter)) {
			$delimiter = '(?:' . implode('|', $delimiter) . ')';
		}

		return preg_replace_callback('/' . $delimiter . '(.)/', function ($match) {
			return strtoupper($match[1]);
		}, $string);
	}

	public static function smartReplace(string $string, array $rules): string
    {
        $search = $rules['search'] ?? null;
        $replacement = $rules['replacement'] ?? null;
        if ($search === null || $replacement === null) {
            return $string;
        }

        $arr = self::smartShild($string, $rules);
        if ($search[0] != '/') $search = "/$search/";
        if (is_callable($replacement)) {
            $str = preg_replace_callback($search, $replacement, $arr['string']);
        } else {
            $str = preg_replace($search, $replacement, $arr['string']);
        }
        
        return self::smartRestore($str, $arr);
    }

	public static function smartSplit(string $string, array $rules): array
	{
		$delimiter = $rules['delimiter'];
		$save = $rules['save'];
		if ( ! is_array($save)) {
			$save = [$save];
		}
		$str = $string;

		if (array_search("'", $save) !== false || array_search('"', $save) !== false) {
			$str = preg_replace('/\\\\' . '\'/', '№№1№№', $str);
			$str = preg_replace('/\\\\"/', '№№2№№', $str);
			$regexp = '/((?:\'.*?\')|(?:".*?"))/';
			preg_match_all($regexp, $str, $strings);
			$str = preg_replace($regexp, '№№3№№', $str);
			$stringsIndex = 0;
		}

		if (array_search('()', $save) !== false) {
			$regexp = '/(?P<re>\(((?>[^\(\)]+)|(?P>re))*\))/';
			preg_match_all($regexp, $str, $brackets);
			$str = preg_replace($regexp, '№№()№№', $str);
			$bracketIndex = 0;
		}
		if (array_search('{}', $save) !== false) {
			$regexp = '/(?P<re>{((?>[^{}]+)|(?P>re))*})/';
			preg_match_all($regexp, $str, $braces);
			$str = preg_replace($regexp, '№№{}№№', $str);
			$braceIndex = 0;
		}
		if (array_search('[]', $save) !== false) {
			$regexp = '/(?P<re>\[((?>[^\[\]]+)|(?P>re))*\])/';
			preg_match_all($regexp, $str, $arrays);
			$str = preg_replace($regexp, '№№[]№№', $str);
			$arrayIndex = 0;
		}

		$arr = preg_split('/\s*' . $delimiter . '\s*/', $str);
		foreach ($arr as &$item) {
			if (isset($arrayIndex)) {
				$item = preg_replace_callback('/№№\[\]№№/', function($matches) use ($arrays, &$arrayIndex) {
					return $arrays[0][$arrayIndex++];
				}, $item);
			}
			if (isset($braceIndex)) {
				$item = preg_replace_callback('/№№\{\}№№/', function($matches) use ($braces, &$braceIndex) {
					return $braces[0][$braceIndex++];
				}, $item);
			}
			if (isset($bracketIndex)) {
				$item = preg_replace_callback('/№№\(\)№№/', function($matches) use ($brackets, &$bracketIndex) {
					return $brackets[0][$bracketIndex++];
				}, $item);
			}
			if (isset($stringsIndex)) {
				$item = preg_replace_callback('/№№3№№/', function($matches) use ($strings, &$stringsIndex) {
					return $strings[0][$stringsIndex++];
				}, $item);
			}

			$item = str_replace('№№1№№', '\\\'', $item);
			$item = str_replace('№№2№№', '\\"', $item);
		}
		unset($item);

		return $arr;
	}

    private static function smartShild(string $string, array $rules): array
    {
        $save = $rules['save'] ?? '"';
        if ( ! is_array($save)) {
            $save = [$save];
        }
        $str = $string;

        $result = [];

        if (array_search("'", $save) !== false || array_search('"', $save) !== false) {
            $str = preg_replace('/\\\\' . '\'/', '№№1№№', $str);
            $str = preg_replace('/\\\\"/', '№№2№№', $str);
            $regexp = '/((?:\'.*?\')|(?:".*?"))/';
            preg_match_all($regexp, $str, $strings);
            $str = preg_replace($regexp, '№№3№№', $str);
            $result['strings'] = $strings;
        }
        if (array_search('()', $save) !== false) {
            $regexp = '/(?P<re>\(((?>[^\(\)]+)|(?P>re))*\))/';
            preg_match_all($regexp, $str, $brackets);
            $str = preg_replace($regexp, '№№()№№', $str);
            $result['brackets'] = $brackets;
        }
        if (array_search('{}', $save) !== false) {
            $regexp = '/(?P<re>{((?>[^{}]+)|(?P>re))*})/';
            preg_match_all($regexp, $str, $braces);
            $str = preg_replace($regexp, '№№{}№№', $str);
            $result['braces'] = $braces;
        }
        if (array_search('[]', $save) !== false) {
            $regexp = '/(?P<re>\[((?>[^\[\]]+)|(?P>re))*\])/';
            preg_match_all($regexp, $str, $arrays);
            $str = preg_replace($regexp, '№№[]№№', $str);
            $arrayIndex = 0;
            $result['arrays'] = $arrays;
        }

        $result['string'] = $str;
        return $result;
    }

    private static function smartRestore(string $string, array $list): string
    {
        if (array_key_exists('arrays', $list)) {
            $arr = $list['arrays'];
            $index = 0;
            $string = preg_replace_callback('/№№\[\]№№/', function($matches) use ($arr, &$index) {
                return $arr[0][$index++];
            }, $string);
        }
        if (array_key_exists('braces', $list)) {
            $arr = $list['braces'];
            $index = 0;
            $string = preg_replace_callback('/№№\{\}№№/', function($matches) use ($arr, &$index) {
                return $arr[0][$index++];
            }, $string);
        }
        if (array_key_exists('brackets', $list)) {
            $arr = $list['brackets'];
            $index = 0;
            $string = preg_replace_callback('/№№\(\)№№/', function($matches) use ($arr, &$index) {
                return $arr[0][$index++];
            }, $string);
        }
        if (array_key_exists('strings', $list)) {
            $arr = $list['strings'];
            $index = 0;
            $string = preg_replace_callback('/№№3№№/', function($matches) use ($arr, &$index) {
                return $arr[0][$index++];
            }, $string);
        }

        $string = str_replace('№№1№№', '\\\'', $string);
        $string = str_replace('№№2№№', '\\"', $string);
        return $string;
    }
}
