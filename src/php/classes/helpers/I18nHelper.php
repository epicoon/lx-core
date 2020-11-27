<?php

namespace lx;

/**
 * Class I18nHelper
 * @package lx
 */
class I18nHelper
{
	/**
	 * @param string $data - text for internacionalization
	 * @param I18nMap|array $map - map of translates
     * @return string
	 */
	public static function localize($data, $map)
	{
        if ($map instanceof I18nMap) {
            $map = $map->getFullMap();
        }

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

			$translation = self::translate($key, $map);
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
	 * @param Plugin $plugin - Plugin which is context for internacionalization
	 * @param string $data - text for internacionalization
     * @return string
	 */
	public static function localizePlugin($plugin, $data)
	{
		$map = $plugin->i18nMap;
		$fullMap = $map->getFullMap();
		return self::localize($data, $fullMap);
	}

	/**
	 * @param string $key - key of string to translate
	 * @param I18nMap|array $map - map of translates
     * @param array $params
     * @param string|null $lang
     * @return string
	 */
	public static function translate($key, $map, $params = [], $lang = null)
	{
	    if ($map instanceof I18nMap) {
	        $map = $map->getFullMap();
        }

		$lang = $lang ?? \lx::$app->language->current;

		if (array_key_exists($lang, $map) && array_key_exists($key, $map[$lang])) {
		    $str = $map[$lang][$key];
            if (!empty($params)) {
                foreach ($params as $paramName => $paramValue) {
                    if (strpos($str, '^{' . $paramName . '}') !== false) {
                        $paramValue = self::translate($paramValue, $map, [], $lang);
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
     * @return string
	 */
	private static function parseParams($params)
	{
        $result = [];
		$arr = preg_split('/\s*,\s*/', trim($params, ':'));
		foreach ($arr as $item) {
			$pare = preg_split('/\s*:\s*/', $item);
			$key = $pare[0];
			$value = count($pare) > 1 ? $pare[1] : $pare[0];
			$result[$key] = $value;
		}
		return $result;
	}
}
