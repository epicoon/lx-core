<?php

namespace lx;

class I18nHelper {
	/**
	 * @param $data string текст для интернационализации
	 * @param $map array массив основных переводов
	 * */
	public static function localize($data, $map) {
		preg_match_all('/#lx:i18n([\w\W]*?)i18n:lx#/', $data, $matches);
		$keys = array_unique($matches[1]);
		foreach ($keys as $match) {
			$arr = preg_split('/^([^,]+?)\s*,\s*([\w\W]+)$/', $match, null, PREG_SPLIT_DELIM_CAPTURE);
			if (count($arr) == 1) {
				$key = trim($arr[0], '\'"');
				$params = null;
			} else {
				$key = trim($arr[1], '\'"');
				$params = trim($arr[2], ' {}');
			}

			$translation = self::translate($key, $map);
			if ($params) {
				$translation = self::parseParams($translation, $params);
			}

			$data = preg_replace('/#lx:i18n'.$match.'i18n:lx#/', $translation, $data);
		}
		return $data;
	}

	/**
	 * @param $plugin плагин, в рамках которого осуществляем интернационализацию
	 * @param $data текст для интернационализации
	 * */
	public static function localizePlugin($plugin, $data) {
		$map = $plugin->i18nMap;
		$fullMap = $map->getFullMap();
		return self::localize($data, $fullMap);
	}

	/**
	 * @param $key ключ переводимого текста
	 * @param $map основная карта переводов
	 * */
	public static function translate($key, $map) {
		$lang = \lx::$app->language->current;

		if (array_key_exists($lang, $map) && array_key_exists($key, $map[$lang])) {
			return $map[$lang][$key];
		}

		//todo - логировать отсутствие локализации?
		return $key;
	}

	/**
	 * @param $translation
	 * @param $params прилетает строкой вида 'k1: v1, k2: v2'
	 * */
	private static function parseParams($translation, $params) {
		$result = $translation;

		$fromPhp = $params{0} == ':';
		$arr = preg_split('/\s*,\s*/', trim($params, ':'));
		foreach ($arr as $item) {
			$pare = preg_split('/\s*:\s*/', $item);
			$key = $pare[0];
			$value = count($pare) > 1 ? $pare[1] : $pare[0];
			$result = $fromPhp
				? str_replace('${'.$key.'}', $value, $result)
				: str_replace('${'.$key.'}', '\'+'.$value.'+\'', $result);
		}

		return $result;
	}
}
