<?php

namespace lx;

class ConsoleInput extends AbstractConsoleInput
{
    private array $enteredChars = [];
    private int $cursorPosition = 0;
    private bool $passwordMode = false;

    public function setPasswordMode(bool $mode = true): ConsoleInput
    {
        $this->passwordMode = $mode;
        return $this;
    }

    /**
     * @return string
     */
    public function run()
    {
        readline_callback_handler_install('', function () {
        });

        if ($this->hint) {
            Console::out($this->hint, $this->hintDecor);
        }

        while (true) {
            list($char, $type) = $this->defineInput();
            if ($type == self::DIRECTIVE_ENTER) {
                echo PHP_EOL;
                break;
            }

            if ($this->hasCallback($type)) {
                $this->runCallback($type);
                continue;
            }

            switch ($type) {
                case self::DIRECTIVE_INPUT:
                    $this->insChar($char);
                    break;
                case self::DIRECTIVE_BACKSPACE:
                    $this->backspace();
                    break;
                case self::DIRECTIVE_DELETE:
//                    TODO
                    break;
                case self::DIRECTIVE_LEFT:
                    $this->toLeft();
                    break;
                case self::DIRECTIVE_RIGHT:
                    $this->toRight();
                    break;
                case self::DIRECTIVE_HOME:
                    $this->toHome();
                    break;
                case self::DIRECTIVE_END:
                    $this->toEnd();
                    break;
            }
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
        echo str_repeat(' ', mb_strlen($this->hint ?? '') + count($this->enteredChars) + 1);
        echo "\r";
        if ($this->hint) {
            Console::out($this->hint, $this->hintDecor);
        }
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

    private function toLeft(): void
    {
        if ($this->cursorPosition > 0) {
            echo "\033[1D";
            $this->cursorPosition--;
        }
    }

    private function toRight(): void
    {
        if ($this->cursorPosition < count($this->enteredChars)) {
            echo "\033[1C";
            $this->cursorPosition++;
        }
    }

    private function toHome(): void
    {
        if ($this->cursorPosition > 0) {
            echo "\033[" . $this->cursorPosition . "D";
            $this->cursorPosition = 0;
        }
    }

    private function toEnd(): void
    {
        $count = count($this->enteredChars);
        if ($this->cursorPosition < $count) {
            echo "\033[" . ($count - $this->cursorPosition) . "C";
            $this->cursorPosition = $count;
        }
    }
}
