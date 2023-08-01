<?php

namespace lx;

class I18nHelper
{
	public static function localizeText(string $data, I18nMap $map, ?string $lang = null): string
	{
        $map = $map->getFullMap();

		preg_match_all('/#lx:i18n([\w\W]*?)i18n:lx#/', $data, $matches);
		$keys = array_unique($matches[1]);
		foreach ($keys as $match) {
			$arr = preg_split('/^([^,]+?)\s*,\s*([\w\W]+)$/', $match, null, PREG_SPLIT_DELIM_CAPTURE);
			if (count($arr) == 1) {
				$key = trim($arr[0], '\'"');
				$params = [];
			} else {
				$key = trim($arr[1], '\'"');
				$params = self::parseParams(trim($arr[2], ' {}'));
			}

			$translation = self::localizeKey($key, $map, [], $lang);
			if (!empty($params)) {
                foreach ($params as $paramName => $paramValue) {
                    $translation = str_replace(
                        '${' . $paramName . '}',
                        '\'+' . $paramValue . '+\'',
                        $translation
                    );
                }
			}

			$reg = addcslashes('/#lx:i18n' . $match . 'i18n:lx#/', '()?{}[]');
			$data = preg_replace($reg, $translation, $data);
		}
		return $data;
	}

	/**
	 * @param I18nMap|array $map - map of translates
	 */
	public static function localizeKey(string $key, $map, array $params = [], ?string $lang = null): string
	{
	    if ($map instanceof I18nMap) {
	        $map = $map->getFullMap();
        }

		$lang = $lang ?? \lx::$app->language->currentCode;

		if (array_key_exists($lang, $map) && array_key_exists($key, $map[$lang])) {
		    $str = $map[$lang][$key];
            if (!empty($params)) {
                foreach ($params as $paramName => $paramValue) {
                    if (strpos($str, '^{' . $paramName . '}') !== false) {
                        $paramValue = self::localizeKey($paramValue, $map, [], $lang);
                        $str = str_replace('^{' . $paramName . '}', $paramValue, $str);
                    } else {
                        $str = str_replace('${' . $paramName . '}', $paramValue, $str);
                    }
                }
            }

			return $str;
		}

		return $key;
	}

	/**
	 * @param string $params - example: 'k1: v1, k2: v2'
	 */
	private static function parseParams(string $params): array
	{
        $result = [];
		$arr = preg_split('/\s*,\s*/', trim($params, ':'));
		foreach ($arr as $item) {
			$pare = preg_split('/\s*:\s*/', $item);
			$key = $pare[0];
			$value = count($pare) > 1 ? $pare[1] : $pare[0];
            $key = preg_replace('/(^[\s\r\n]+|[\s\r\n]+$)/', '', $key);
            $value = preg_replace('/(^[\s\r\n]+|[\s\r\n]+$)/', '', $value);
            $result[$key] = $value;
		}
		return $result;
	}
}
