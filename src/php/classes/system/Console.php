<?php

namespace lx;

class Console {
	private static $inputContext = null;

	/**
	 * Вывод строки с возможностью декорирования
	 * Пример:
	 * Console::out('ERROR: ', $msg, ['color' => 'white', 'back' => 'red', 'decor' => 'b']);
	 *
	 * Доступные цвета и оформления в методе ::decorateString()
	 * */
	public static function out(/*...args*/) {
		$args = func_get_args();
		if (empty($args)) return;

		$msg = self::getString($args);
		echo($msg);
	}

	/**
	 * Вывод строки с переводом на новую строку
	 * */
	public static function outln(/*...args*/) {
		$args = func_get_args();
		if (empty($args)) {
			echo PHP_EOL;
			return;
		}

		call_user_func_array([self::class, 'out'], $args);
		echo PHP_EOL;		
	}

	/**
	 * Ввод из консоли
	 * */
	public static function in($hint = 'Input: ', $hintDecor = [], $textDecor = [], $callbacks = []) {
		self::$inputContext = new ConsoleInputContext($hint, $hintDecor, $textDecor, $callbacks);
		$result = self::$inputContext->run();
		self::$inputContext = null;
		return $result;
	}

	/**
	 *
	 * */
	public static function getCurrentInput() {
		if (self::$inputContext === null) {
			return null;
		}

		return self::$inputContext->getText();
	}

	/**
	 * Заменить текущий ввод в консоли
	 * */
	public static function replaceInput($text) {
		if (self::$inputContext === null) {
			return;
		}

		self::$inputContext->replace($text);
	}

	/**
	 * @var $table - двумерный массив: строки (rows), в каждой строке колонки, значения - строковые (string) данные
	 * @var $char - символ, добавлением которого строковые данные будут выравнены
	 * //todo - добавить выравнивание
	 * */
	public static function normalizeTable($table, $char = ' ') {
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

	/**
	 * Возвращает декорированную строку
	 * */
	private static function getString($args) {
		$opts = [];
		if (is_array(end($args))) $opts = array_pop($args);
		if (empty($args)) return '';

		$msg = implode(' ', $args);
		$msg = empty($opts)
			? $msg
			: self::decorateString($msg, $opts);

		return $msg;
	}

	/**
	 * Цветовой вывод в консоль, пример:
	 * echo("\033[31;1;40m Внимание \033[0m" . PHP_EOL);
	 *
	 * tput sgr0    Возврат цвета в "нормальное" состояние
	 *
	 * \033[0m    все атрибуты по умолчанию
	 * \033[1m    жирный шрифт (интенсивный цвет)
	 * \033[2m    полу яркий цвет (тёмно-серый, независимо от цвета)
	 * \033[4m    подчеркивание
	 * \033[5m    мигающий
	 * \033[7m    реверсия (знаки приобретают цвет фона, а фон -- цвет знаков)
	 *
	 * \033[22m    установить нормальную интенсивность
	 * \033[24m    отменить подчеркивание
	 * \033[25m    отменить мигание
	 * \033[27m    отменить реверсию
	 *
	 * \033[30    чёрный цвет знаков
	 * \033[31    красный цвет знаков
	 * \033[32    зелёный цвет знаков
	 * \033[33    желтый цвет знаков
	 * \033[34    синий цвет знаков
	 * \033[35    фиолетовый цвет знаков
	 * \033[36    цвет морской волны знаков
	 * \033[37    серый цвет знаков
	 *
	 * \033[40    чёрный цвет фона
	 * \033[41    красный цвет фона
	 * \033[42    зелёный цвет фона
	 * \033[43    желтый цвет фона
	 * \033[44    синий цвет фона
	 * \033[45    фиолетовый цвет фона
	 * \033[46    цвет морской волны фона
	 * \033[47    серый цвет фона
	 * */
	private static function decorateString($msg, $opts) {
		$codes = [];
		if (array_key_exists('color', $opts)) {
			switch ($opts['color']) {
				case 'black'    : $codes[] = 30; break;
				case 'red'      : $codes[] = 31; break;
				case 'green'    : $codes[] = 32; break;
				case 'yellow'   : $codes[] = 33; break;
				case 'blue'     : $codes[] = 34; break;
				case 'violet'   : $codes[] = 35; break;
				case 'turquoise': $codes[] = 36; break;
				case 'white'    :
				case 'gray'     : $codes[] = 37; break;
			}
		}
		if (array_key_exists('back', $opts)) {
			switch ($opts['back']) {
				case 'black'    : $codes[] = 40; break;
				case 'red'      : $codes[] = 41; break;
				case 'green'    : $codes[] = 42; break;
				case 'yellow'   : $codes[] = 43; break;
				case 'blue'     : $codes[] = 44; break;
				case 'violet'   : $codes[] = 45; break;
				case 'turquoise': $codes[] = 46; break;
				case 'white'    :
				case 'gray'     : $codes[] = 47; break;
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

	/**
	 * Вызов метода некоторого объекта с аргументами, введенными через командную строку
	 * //todo - раньше код был в модуле. Перспективный, перенес сюда. Пока не используется
	 * */
	public function callMethod($object, $method) {
		if (!method_exists($object, $method)) {
			Console::out(' ERROR ', ['color' => 'gray', 'back' => 'red', 'decor' => 'b']);
			Console::outln(" unknown method '$method'", ['decor' => 'u']);
			return;
		}

		// Определим аргументы для метода
		// Делаем карту по аргументам, которые есть в методе
		$rm = new \ReflectionMethod($object, $method);
		$paramsList = [];
		$firstLatters = [];
		$defaults = [];
		foreach ($rm->getParameters() as $param) {
			$name = $param->name;
			if ($param->isDefaultValueAvailable()) {
				$defaults[$name] = $param->getDefaultValue();
			}
			$paramsList[] = $name;
			if (array_key_exists($name{0}, $firstLatters)) {
				$firstLatters[$name{0}]++;
			} else {
				$firstLatters[$name{0}] = 1;
			}
		}

		// Разбираем аргументы на длинные и короткие
		$shortopts = '';
		$longopts = [];
		foreach ($paramsList as $param) {
			$longopts[] = $param . '::';
		}
		foreach ($firstLatters as $latter => $count) {
			if ($count > 1) continue;
			$shortopts .= $latter . '::';
		}

		// Получаем параметры из командной строки
		$options = getopt($shortopts, $longopts);

		// Находим соответствия между ожидаемыми и введенными параметрами
		$params = [];
		foreach ($paramsList as $paramName) {
			if (array_key_exists($paramName, $options)) {
				$params[$paramName] = $options[$paramName];
			} elseif (array_key_exists($paramName{0}, $options)) {
				$params[$paramName] = $options[$paramName{0}];
			} elseif (array_key_exists($paramName, $defaults)) {
				$params[$paramName] = $defaults[$paramName];
			} else {
				Console::out(' ERROR ', ['color' => 'gray', 'back' => 'red', 'decor' => 'b']);
				$msg = " parameter '--$paramName'";
				if ($firstLatters[$paramName{0}] == 1) $msg .= " (or '-{$paramName[0]}')";
				Console::outln($msg, 'is required', ['decor' => 'u']);
				return;
			}
		}

		// Вызываем
		call_user_func_array([$object, $method], $params);
	}
}

//=============================================================================================================================


//=============================================================================================================================
class ConsoleInputContext {
	private
		$hint,
		$hintDecor,
		$textDecor,
		$callbacks,
		$intercepts,

		$enteredChars,
		$cursorPosition;

	public function __construct($hint, $hintDecor, $textDecor, $callbacks) {
		$this->hint = $hint;
		$this->hintDecor = $hintDecor;
		$this->textDecor = $textDecor;
		$this->callbacks = $callbacks;

		$this->intercepts = array_key_exists('intercept', $callbacks)
			? $callbacks['intercept']
			: [];

		$this->passwordMode = false;
		$this->enteredChars = [];
		$this->cursorPosition = 0;
	}

	/**************************************************************************************************************************
	 * PUBLIC
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	public function setPasswordMode($mode = true) {
		$this->passwordMode = $mode;
	}

	/**
	 *
	 * */
	public function run() {
		// $interceptedCodes = [9];
		readline_callback_handler_install('', function(){});
		
		Console::out($this->hint, $this->hintDecor);
		while (true) {
			$strChar = stream_get_contents(STDIN, 1);

			$ord = ord($strChar);
			// Enter
			if ($ord == 10) {
				echo PHP_EOL;
				break;
			}

			// echo $strChar . PHP_EOL;
			// return;

			if (array_key_exists($ord, $this->intercepts)) {
				list($context, $func) = $this->extractCallback($this->intercepts, $ord);
				if ($func === null) continue;
				if ($context === null) {
					$func();
				} else {
					call_user_method($func, $context);
				}
				continue;

			// Backspace
			} elseif ($ord == 127) {
				$this->backspace();
				continue;

			// Управление курсором
			} elseif ($ord == 27) {
				$this->driveCursor($strChar);
				continue;

			// Кириллица на юникоде
			} elseif ($ord == 208 || $ord == 209) {
				$strChar .= stream_get_contents(STDIN, 1);
			}

			$this->insChar($strChar);
		}

		return implode('', $this->enteredChars);
	}

	/**
	 *
	 * */
	public function getText() {
		return implode('', $this->enteredChars);
	}

	/**
	 *
	 * */
	public function replace($text) {
		$arr = [];
		if (is_string($text)) {
			$i = 0;
			$len = mb_strlen($text);
			while ($i < $len) $arr[] = mb_substr($text, $i++, 1);
		} elseif (is_array($text)) {
			$arr = $text;
		}

		$this->innerClear();
		$this->enteredChars = $arr;
		$this->printEntered();
		$this->cursorTo( count($this->enteredChars) );
	}

	/**
	 *
	 * */
	public function clear() {
		$this->innerClear();
		$this->enteredChars = [];
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function insChar($strChar) {
		// if (!preg_match('/^[a-zA-Z0-9.!@?#"$%&:\';()*\+,\/;\-=[\\\]\^_{|}<>~` а-яА-ЯёЁ]$/', $strChar)) {
		// 	return;
		// }
		
		$cursorPosition = $this->cursorPosition;
		$this->innerClear();
		array_splice($this->enteredChars, $cursorPosition, 0, [$strChar]);
		$this->printEntered();
		$this->cursorTo($cursorPosition + 1);
	}

	/**
	 *
	 * */
	private function backspace() {
		if ($this->cursorPosition == 0) {
			return;
		}

		$cursorPosition = $this->cursorPosition;
		$this->innerClear();
		array_splice($this->enteredChars, $cursorPosition - 1, 1);
		$this->printEntered();
		$this->cursorTo($cursorPosition - 1);
	}

	/**
	 *
	 * */
	private function innerClear() {
		echo "\r";
		echo str_repeat(' ', mb_strlen($this->hint) + count($this->enteredChars) + 1);
		echo "\r";
		Console::out($this->hint, $this->hintDecor);
		$this->cursorPosition = 0;
	}

	/**
	 *
	 * */
	private function printEntered() {
		if (empty($this->enteredChars)) {
			return;
		}

		if ($this->passwordMode) {
			Console::out(str_repeat('*', count($this->enteredChars)), $this->textDecor);
		} else {
			Console::out(implode($this->enteredChars), $this->textDecor);
		}
		echo "\033[" . count($this->enteredChars) . "D";
	}

	/**
	 *
	 * */
	private function cursorTo($newPosition) {
		if ($newPosition == $this->cursorPosition) {
			return;
		}

		if ($newPosition > $this->cursorPosition) {
			$shift = $newPosition - $this->cursorPosition;
			echo "\033[" . $shift . "C";
		} else {
			$shift = $this->cursorPosition - $newPosition;
			echo "\033[" . $shift . "D";
		}

		$this->cursorPosition = $newPosition;
	}

	/**
	 * http://www.termsys.demon.co.uk/vtansi.htm#cursor
	 * */
	private function driveCursor($strChar) {
		$arr = [
			$strChar,
			stream_get_contents(STDIN, 1),
			stream_get_contents(STDIN, 1)
		];
		// To right
		if ($arr[2] == 'C') {
			if ($this->cursorPosition < count($this->enteredChars)) {
				echo "\033[1C";
				$this->cursorPosition++;
			}
		// To left
		} elseif ($arr[2] == 'D') {
			if ($this->cursorPosition > 0) {
				echo "\033[1D";
				$this->cursorPosition--;
			}
		// To home
		} elseif ($arr[2] == 'H') {
			if ($this->cursorPosition > 0) {
				echo "\033[" . $this->cursorPosition . "D";
				$this->cursorPosition = 0;
			}
		// To end
		} elseif ($arr[2] == 'F') {
			$count = count($this->enteredChars);
			if ($this->cursorPosition < $count) {
				echo "\033[" . ($count - $this->cursorPosition) . "C";
				$this->cursorPosition = $count;
			}

		} else {
			if ($arr[2] == 'A') {
				if (array_key_exists('up', $this->callbacks)) {
					list($context, $func) = $this->extractCallback($this->callbacks, 'up');
					if ($func === null) return;
					if ($context === null) {
						$func();
					} else {
						call_user_method($func, $context);
					}
				}
			} elseif ($arr[2] == 'B') {
				if (array_key_exists('down', $this->callbacks)) {
					list($context, $func) = $this->extractCallback($this->callbacks, 'down');
					if ($func === null) return;
					if ($context === null) {
						$func();
					} else {
						call_user_method($func, $context);
					}
				}
			}
		}
	}

	/**
	 *
	 * */
	private function extractCallback($arr, $key) {
		$context = null;
		$func = null;
		if (is_array($arr[$key]) && is_callable($arr[$key][1])) {
			$context = $arr[$key][0];
			$func = $arr[$key][1];
		} elseif (is_callable($arr[$key])) {
			$func = $arr[$key];
		}
		return [$context, $func];
	}
}
