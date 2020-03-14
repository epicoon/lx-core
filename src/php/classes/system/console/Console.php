<?php

namespace lx;

/**
 * Class Console
 * @package lx
 */
class Console
{
	/** @var ConsoleInputContext */
	private static $inputContext = null;

	/**
	 * Print a decorated string
	 *
	 * Example:
	 * Console::out('ERROR: ', $msg, ['color' => 'white', 'back' => 'red', 'decor' => 'b']);
	 *
	 * Look for available decore options in the method [[decorateString]]
	 *
	 * @param array ...$args
	 */
	public static function out(...$args)
	{
		if (empty($args)) return;

		$msg = self::getString($args);
		echo($msg);
	}

	/**
	 * @param array ...$args
	 */
	public static function outln(...$args)
	{
		if (empty($args)) {
			echo PHP_EOL;
			return;
		}

		call_user_func_array([self::class, 'out'], $args);
		echo PHP_EOL;
	}

	/**
	 * @param string $hint
	 * @param array $hintDecor
	 * @param array $textDecor
	 * @param array $callbacks
	 * @return string
	 */
	public static function in($hint = 'Input: ', $hintDecor = [], $textDecor = [], $callbacks = [])
	{
		if (is_array($hint)) {
			return self::in(
				$hint['hintText'] ?? 'Input: ',
				$hint['hintDecor'] ?? [],
				$hint['textDecor'] ?? [],
				$hint['callbacks'] ?? []
			);
		}

		self::$inputContext = new ConsoleInputContext($hint, $hintDecor, $textDecor, $callbacks);
		$result = self::$inputContext->run();
		self::$inputContext = null;
		return $result;
	}

	/**
	 * @return string|null
	 */
	public static function getCurrentInput()
	{
		if (self::$inputContext === null) {
			return null;
		}

		return self::$inputContext->getText();
	}

	/**
	 * @param string $text
	 */
	public static function replaceInput($text)
	{
		if (self::$inputContext === null) {
			return;
		}

		self::$inputContext->replace($text);
	}

	/**
	 * @param array $table - array of rows, in each row array of columns with string values
	 * @param string $char - character for align
	 */
	public static function normalizeTable($table, $char = ' ')
	{
		$maxes = [];
		reset($table);
		$key = key($table);
		$columnsCount = count($table[$key]);
		foreach ($table as $row) {
			foreach ($row as $i => $text) {
				if (!is_string($text)) continue;
				if ($i == $columnsCount - 1) continue;
				if (!array_key_exists($i, $maxes)) $maxes[$i] = 0;
				$maxes[$i] = max($maxes[$i], strlen($text));
			}
		}

		foreach ($table as &$row) {
			foreach ($row as $i => &$text) {
				if (!is_string($text)) continue;
				if ($i == $columnsCount - 1) continue;
				$l = $maxes[$i] - strlen($text);
				$text .= str_repeat($char, $l);
			}
		}
		unset($text);
		unset($row);

		return $table;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param array $args
	 * @return string
	 */
	private static function getString($args)
	{
		$opts = [];
		if (is_array(end($args))) {
			$opts = array_pop($args);
		}

		if (empty($args)) {
			return '';
		}

		$msg = implode(' ', $args);
		return empty($opts)
			? $msg
			: self::decorateString($msg, $opts);
	}

	/**
	 * Use colors for console enter, example:
	 * echo("\033[31;1;40m Warning \033[0m" . PHP_EOL);
	 * The same to:
	 * Console::decorateString('Warning', ['color' => 'red', 'back' => 'black', 'decor' => 'b']);
	 *
	 * Return color to default:
	 * tput sgr0
	 *
	 * \033[0m    default attributes
	 * \033[1m    bold font
	 * \033[2m    semi bright color
	 * \033[4m    underline
	 * \033[5m    blinking
	 * \033[7m    reversal(characters acquire a background color, and the background a character color)
	 *
	 * \033[22m    set normal intensity
	 * \033[24m    отменить подчеркивание
	 * \033[25m    cancel underline
	 * \033[27m    cancel reversal
	 *
	 * \033[30    text black color
	 * \033[31    text red color
	 * \033[32    text green color
	 * \033[33    text yellow color
	 * \033[34    text blue color
	 * \033[35    text violet color
	 * \033[36    text maritime color
	 * \033[37    text gray color
	 *
	 * \033[40    background black color
	 * \033[41    background red color
	 * \033[42    background green color
	 * \033[43    background yellow color
	 * \033[44    background blue color
	 * \033[45    background violet color
	 * \033[46    background maritime color
	 * \033[47    background gray color
	 *
	 * @param string $msg
	 * @param array $opts
	 * @return string
	 */
	private static function decorateString($msg, $opts)
	{
		$codes = [];
		if (array_key_exists('color', $opts)) {
			switch ($opts['color']) {
				case 'black'    :
					$codes[] = 30;
					break;
				case 'red'      :
					$codes[] = 31;
					break;
				case 'green'    :
					$codes[] = 32;
					break;
				case 'yellow'   :
					$codes[] = 33;
					break;
				case 'blue'     :
					$codes[] = 34;
					break;
				case 'violet'   :
					$codes[] = 35;
					break;
				case 'turquoise':
					$codes[] = 36;
					break;
				case 'white'    :
				case 'gray'     :
					$codes[] = 37;
					break;
			}
		}
		if (array_key_exists('back', $opts)) {
			switch ($opts['back']) {
				case 'black'    :
					$codes[] = 40;
					break;
				case 'red'      :
					$codes[] = 41;
					break;
				case 'green'    :
					$codes[] = 42;
					break;
				case 'yellow'   :
					$codes[] = 43;
					break;
				case 'blue'     :
					$codes[] = 44;
					break;
				case 'violet'   :
					$codes[] = 45;
					break;
				case 'turquoise':
					$codes[] = 46;
					break;
				case 'white'    :
				case 'gray'     :
					$codes[] = 47;
					break;
			}
		}
		if (array_key_exists('decor', $opts)) {
			$decor = $opts['decor'];
			if (stristr($decor, 'b') !== false) $codes[] = 1;
			if (stristr($decor, 'u') !== false) $codes[] = 4;
		}

		$codes = implode(';', $codes);
		return "\033[" . $codes . 'm' . $msg . "\033[0m";
	}
}
