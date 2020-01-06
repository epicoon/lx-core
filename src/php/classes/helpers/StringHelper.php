<?php

namespace lx;

class StringHelper
{
	public static function camelToSnake($string)
	{
		return lcfirst(preg_replace_callback('/(.)([A-Z])/', function($match) {
			return $match[1] . '_' . strtolower($match[2]) ;
		}, $string));
	}
	
	public static function snakeToCamel($string, $delimiter = '_')
	{
		if (is_array($delimiter)) {
			$delimiter = '('. implode('|', $delimiter) .')';
		}

		return preg_replace_callback('/'. $delimiter .'(.)/', function($match) {
			return strtoupper($match[2]) ;
		}, $string);
	}

	public static function smartSplit($string, $rules)
	{
		$delimiter = $rules['delimiter'];
		$save = $rules['save'];
		$saveStrings = array_search("'", $save) !== false;
		$saveStringsD = array_search('"', $save) !== false;
		$str = $string;

		//TODO строки

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

		$arr = preg_split('/\s*'. $delimiter .'\s*/', $str);
		$index = 0;
		//TODO - по местной логике поддерживается только по одному соответствию на айтем
		foreach ($arr as &$item) {
			if (preg_match('/№№\(\)№№/', $item)) {
				$item = preg_replace('/№№\(\)№№/', $brackets[0][$bracketIndex++], $item);
			}
			if (preg_match('/№№{}№№/', $item)) {
				$item = preg_replace('/№№{}№№/', $braces[0][$braceIndex++], $item);
			}
			if (preg_match('/№№\[\]№№/', $item)) {
				$item = preg_replace('/№№\[\]№№/', $arrays[0][$arrayIndex++], $item);
			}
		}
		unset($item);

		return $arr;
	}
}
