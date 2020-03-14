<?php

namespace lx;

/**
 * Class ConsoleInputContext
 * @package lx
 */
class ConsoleInputContext
{
	/** @var string */
	private $hint;

	/** @var array */
	private $hintDecor;

	/** @var array */
	private $textDecor;

	/** @var array */
	private $callbacks;

	/** @var array */
	private $intercepts;

	/** @var array */
	private $enteredChars;

	/** @var int */
	private $cursorPosition;

	/** @var bool */
	private $passwordMode;

	/**
	 * ConsoleInputContext constructor.
	 * @param string $hint
	 * @param array $hintDecor
	 * @param array $textDecor
	 * @param array $callbacks
	 */
	public function __construct($hint, $hintDecor, $textDecor, $callbacks)
	{
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


	/*******************************************************************************************************************
	 * PUBLIC
	 ******************************************************************************************************************/

	/**
	 * @param bool $mode
	 */
	public function setPasswordMode($mode = true)
	{
		$this->passwordMode = $mode;
	}

	/**
	 * @return string
	 */
	public function run()
	{
		// $interceptedCodes = [9];
		readline_callback_handler_install('', function () {
		});

		Console::out($this->hint, $this->hintDecor);
		while (true) {
			$strChar = stream_get_contents(STDIN, 1);

			$ord = ord($strChar);
			// Enter
			if ($ord == 10) {
				echo PHP_EOL;
				break;
			}

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

				// Cursor
			} elseif ($ord == 27) {
				$this->driveCursor($strChar);
				continue;

				// Cyrillic
			} elseif ($ord == 208 || $ord == 209) {
				$strChar .= stream_get_contents(STDIN, 1);
			}

			$this->insChar($strChar);
		}

		return implode('', $this->enteredChars);
	}

	/**
	 * @return string
	 */
	public function getText()
	{
		return implode('', $this->enteredChars);
	}

	/**
	 * @param array|string $text
	 */
	public function replace($text)
	{
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
		$this->cursorTo(count($this->enteredChars));
	}

	/**
	 * Clear printed text
	 */
	public function clear()
	{
		$this->innerClear();
		$this->enteredChars = [];
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $strChar
	 */
	private function insChar($strChar)
	{
		$cursorPosition = $this->cursorPosition;
		$this->innerClear();
		array_splice($this->enteredChars, $cursorPosition, 0, [$strChar]);
		$this->printEntered();
		$this->cursorTo($cursorPosition + 1);
	}

	/**
	 * Action for backspace
	 */
	private function backspace()
	{
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
	 * Clear printed text process
	 */
	private function innerClear()
	{
		echo "\r";
		echo str_repeat(' ', mb_strlen($this->hint) + count($this->enteredChars) + 1);
		echo "\r";
		Console::out($this->hint, $this->hintDecor);
		$this->cursorPosition = 0;
	}

	/**
	 * Print text process
	 */
	private function printEntered()
	{
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
	 * @param int $newPosition
	 */
	private function cursorTo($newPosition)
	{
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
	 * @param string $strChar
	 */
	private function driveCursor($strChar)
	{
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
					$this->callCallback($context, $func);
				}
			} elseif ($arr[2] == 'B') {
				if (array_key_exists('down', $this->callbacks)) {
					list($context, $func) = $this->extractCallback($this->callbacks, 'down');
					$this->callCallback($context, $func);
				}
			}
		}
	}

	/**
	 * @param array $arr
	 * @param string $key
	 * @return array
	 */
	private function extractCallback($arr, $key)
	{
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

	/**
	 * @param object|null $context
	 * @param string|callable $func
	 */
	private function callCallback($context, $func)
	{
		if ($func === null) return;
		if (!is_object($context) && is_callable($func)) {
			$func();
		} elseif (is_string($func) && method_exists($context, $func)) {
			call_user_method($func, $context);
		}
	}
}
