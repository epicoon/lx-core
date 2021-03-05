<?php

namespace lx;

/**
 * Class StringHelper
 * @package lx
 */
class StringHelper
{
	/**
	 * @param string $string
	 * @return string
	 */
	public static function camelToSnake($string)
	{
		return lcfirst(preg_replace_callback('/(.)([A-Z])/', function ($match) {
			return $match[1] . '_' . strtolower($match[2]);
		}, $string));
	}

	/**
	 * @param string $string
	 * @param string $delimiter
	 * @return string
	 */
	public static function snakeToCamel($string, $delimiter = '_')
	{
		if (is_array($delimiter)) {
			$delimiter = '(?:' . implode('|', $delimiter) . ')';
		}

		return preg_replace_callback('/' . $delimiter . '(.)/', function ($match) {
			return strtoupper($match[1]);
		}, $string);
	}

	/**
	 * @param string $string
	 * @param array $rules
	 * @return array
	 */
	public static function smartSplit($string, $rules)
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
		$index = 0;
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
}
