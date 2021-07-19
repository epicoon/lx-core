<?php

namespace lx;

class ConsoleInputContext
{
	private string $hint;
	private array $hintDecor;
	private array $textDecor;
	private array $callbacks;
	private array $intercepts;
	private array $enteredChars;
	private int $cursorPosition;
	private bool $passwordMode;

	public function __construct(string $hint, array $hintDecor, array $textDecor, array $callbacks)
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

	public function setPasswordMode(bool $mode = true): void
	{
		$this->passwordMode = $mode;
	}

	public function run(): string
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

	public function getText(): string
	{
		return implode('', $this->enteredChars);
	}

	public function replace(string $text): void
	{
		$arr = [];
        $i = 0;
        $len = mb_strlen($text);
        while ($i < $len) {
            $arr[] = mb_substr($text, $i++, 1);
        }

		$this->innerClear();
		$this->enteredChars = $arr;
		$this->printEntered();
		$this->cursorTo(count($this->enteredChars));
	}

	public function clear(): void
	{
		$this->innerClear();
		$this->enteredChars = [];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function insChar(string $strChar): void
	{
		$cursorPosition = $this->cursorPosition;
		$this->innerClear();
		array_splice($this->enteredChars, $cursorPosition, 0, [$strChar]);
		$this->printEntered();
		$this->cursorTo($cursorPosition + 1);
	}

	private function backspace(): void
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

	private function innerClear(): void
	{
		echo "\r";
		echo str_repeat(' ', mb_strlen($this->hint) + count($this->enteredChars) + 1);
		echo "\r";
		Console::out($this->hint, $this->hintDecor);
		$this->cursorPosition = 0;
	}

	private function printEntered(): void
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

	private function cursorTo(int $newPosition): void
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

	private function driveCursor(string $strChar): void
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
					$this->runCallback($context, $func);
				}
			} elseif ($arr[2] == 'B') {
				if (array_key_exists('down', $this->callbacks)) {
					list($context, $func) = $this->extractCallback($this->callbacks, 'down');
					$this->runCallback($context, $func);
				}
			}
		}
	}

	private function extractCallback(array $arr, string $key): array
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
	private function runCallback($context, $func): void
	{
		if ($func === null) return;
		if (!is_object($context) && is_callable($func)) {
			$func();
		} elseif (is_string($func) && method_exists($context, $func)) {
			call_user_method($func, $context);
		}
	}
}
