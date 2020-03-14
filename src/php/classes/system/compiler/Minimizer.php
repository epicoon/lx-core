<?php

namespace lx;

class Minimizer
{
	/**
	 * Удаление всех комментариев
	 */
	public static function cutComments($text)
	{
		$text = preg_replace('/\/\/\*.*?(\\\n|\n|$)/', '', $text);
		$text = preg_replace('/\/\*[\w\W]*?\*\//', '', $text);
		$text = preg_replace('/(?<!http:|https:)\/\/.*?(\\\n|\n|$)/', '', $text);
		return $text;
	}

	/**
	 * Удаление всех ненужных пробельных символов
	 */
	public static function clearSpaces($text)
	{
		// Экранировать строки
		list ($text, $strings) = self::maskStrings($text);

		// Консервирует нужные пробелы
		// Пробел между словами
		$text = preg_replace('/\b +\b/', '№№', $text);
		// Пробел после закончившегося слова перед ковычкой или символом '<'
		$text = preg_replace('/\b ([\'"<])/', '№№$1', $text);
		// Пробел после кавычки перед начинающимся словом
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

		// Вернуть строки
		$text = self::returnStrings($text, $strings);

		return $text;
	}

	private static function maskStrings($text)
	{
		$parts = preg_split('/(?:(\'[^\']*?\')|("[^"]*?")|(`[^`]*?`))/', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		$maskedText = '';
		$stringsMap = [
			'1' => [],
			'2' => [],
			'3' => [],
		];
		foreach ($parts as $i => $part) {
			if ($i % 2) {
				$quote = $part{0};
				if ($quote == '\'') {
					$maskedText .= '#lx:_mstr[1,' . count($stringsMap['1']) . ']';
					$stringsMap['1'][] = $part;
				} elseif ($quote == '"') {
					$maskedText .= '#lx:_mstr[2,' . count($stringsMap['2']) . ']';
					$stringsMap['2'][] = $part;
				} elseif ($quote == '`') {
					$maskedText .= '#lx:_mstr[3,' . count($stringsMap['3']) . ']';
					$stringsMap['3'][] = $part;
				}
			} else {
				$maskedText .= $part;
			}
		}
		return [$maskedText, $stringsMap];
	}

	private static function returnStrings($text, $strings)
	{
		$result = preg_replace_callback('/#lx:_mstr\[(\d+?),(\d+?)\]/', function ($match) use ($strings) {
			return $strings[$match[1]][$match[2]];
		}, $text);
		return $result;
	}
}
