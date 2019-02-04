<?php

namespace lx;

class Minimizer {
	/**
	 * Удаление всех комментариев
	 * */
	public static function cutComments($text) {
		$text = preg_replace('/\/\/\*.*?(\\\n|\n|$)/', '', $text);
		$text = preg_replace('/\/\*[\w\W]*?\*\//', '', $text);
		$text = preg_replace('/(?<!http:|https:)\/\/.*?(\\\n|\n|$)/', '', $text);
		return $text;
	}

	/**
	 * Удаление всех ненужных пробельных символов
	 * */
	public static function clearSpacesForce($text) {
		// Консервирует нужные пробелы
		$text = preg_replace('/\b +\b/', '№№', $text);
		// Пробел после закончившегося слова, перед ковычкой или символом '<'
		$text = preg_replace('/\b ([\'"<])/', '№№$1', $text);
		// Пробел после кавычки, перед начинающимся словом
		$text = preg_replace('/([\'"]) \b/', '$1№№', $text);
		// Перенос после else
		$text = preg_replace('/else\s*?/', 'else№№', $text);
		// Защита от +a + +b => +a++b
		$text = str_replace(' +', '№№+', $text);
		// Пробел между двумя слэшами /
		$text = str_replace('/ /', '/№№/', $text);
		// Экранированные пробелы
		$text = str_replace('\\ ', '\\№№', $text);

		// Вырезает все пробельные символы
		$text = preg_replace('/\s+/', '', $text);
		// Возвращает нужные пробелы
		$text = str_replace('№№', ' ', $text);

		// Некоторые коррекции
		$text = str_replace(')else ', ');else ', $text);
		$text = preg_replace('/}(?!(?:else|catch|finally|while))(\w)/', '};$1', $text);

		return $text;
	}

	/**
	 * Удаление всех ненужных пробельных символов, не затрагивая то, что находится в одинарных ковычках
	 * */
	public static function clearSpaces($text) {
		return $text;

		$arr = explode("'", $text);

		foreach ($arr as $i => $item) {
			if ($i % 2) continue;
			$arr[$i] = self::clearSpacesForce($item);
		}

		return implode("'", $arr);
	}

	/**
	 * todo <json-дрочево>!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	 * копия метода clearSpaces - в нем заглушка стоит, чтобы он на самом деле ничего не вырезал - для отладки так удобнее
	 * а это вариант метода, в котором все работает - необходимо таки в некоторых местах избавляться от переносов (см JsCompiler::compileCodeInString())
	 * все из-за ебаного json-на, он видите ли не умеет работать с переносами. И с кавычками там херня
	 *
	 * Удаление всех ненужных пробельных символов, не затрагивая то, что находится в одинарных ковычках
	 * */
	public static function clearSpacesKOSTYL($text) {
		$arr = explode("'", $text);

		foreach ($arr as $i => $item) {
			if ($i % 2) continue;
			$arr[$i] = self::clearSpacesForce($item);
		}

		return implode("'", $arr);
	}
}
