<?php

namespace lx;

class ConsoleSelect extends AbstractConsoleInput
{
    private array $options = [];
    private int $index = 0;
    private $withQuit = true;

    public function setOptions(array $options): ConsoleSelect
    {
        $this->options = $options;
        return $this;
    }

    public function setWithQuit(bool $withQuit): ConsoleSelect
    {
        $this->withQuit = $withQuit;
        return $this;
    }

    /**
     * @return int|null
     */
    public function run()
    {
        readline_callback_handler_install('', function () {
        });

        if ($this->hint) {
            Console::outln($this->hint, $this->hintDecor);
        }

        $this->render();
        while (true) {
            list($char, $type) = $this->defineInput();

            if ($type == self::DIRECTIVE_ENTER) {
                if ($this->index < $this->getLinesCount() - 1) {
                    $shift = $this->getLinesCount() - 1 - $this->index;
                    echo chr(27) . "[" . $shift . "B";
                }
                echo PHP_EOL;
                $result = ($this->index == count($this->options)) ? null : $this->index;
                break;
            }

            switch ($type) {
                case self::DIRECTIVE_UP:
                    $this->toUp();
                    break;
                case self::DIRECTIVE_DOWN:
                    $this->toDown();
                    break;
                case self::DIRECTIVE_INPUT:
                    $this->moveTo($char);
                    break;
            }
        }

        return $result;
    }

    private function toUp(): void
    {
        if ($this->index == 0) {
            return;
        }

        $this->prepareCursor();
        $this->index--;
        $this->render();
    }

    private function toDown(): void
    {
        if ($this->index == $this->getLinesCount() - 1) {
            return;
        }

        $this->prepareCursor();
        $this->index++;
        $this->render();
    }

    private function moveTo(string $char): void
    {
        if ($this->withQuit && $char == 'q') {
            $this->prepareCursor();
            $this->index = count($this->options);
            $this->render();
            return;
        }

        $int = (int)$char;
        if ($char < 1 || $char > $this->getLinesCount()) {
            return;
        }

        $this->prepareCursor();
        $this->index = $int - 1;
        $this->render();
    }

    private function prepareCursor()
    {
        $linesCount = $this->getLinesCount();

        // Set cursor up x lines
        if ($this->index) {
            echo chr(27) . "[" . ($this->index) . "A";
        }
    }

    private function render(): void
    {
        $linesCount = $this->getLinesCount();
        // Set cursor to first column
        echo chr(27) . "[0G";

        foreach ($this->getLines() as $i => $line) {
            $out = '';
            if ($i < $linesCount - 1) {
                $out = ($i + 1) . '. ';
            } elseif ($i == $linesCount - 1) {
                if ($this->withQuit) {
                    $out = 'q. ';
                } else {
                    $out = ($i + 1) . '. ';
                }
            }
            $out .= $line;
            if ($i == $this->index) {
                Console::out($out, ['decor' => 'bu']);
            } else {
                Console::out($out);
            }
            if ($i < $linesCount - 1) {
                echo "\n";
            }
        }

        // Set cursor up x lines
        if ($this->index < count($this->options)) {
            echo chr(27) . "[" . ($linesCount - 1 - $this->index) . "A";
        }
    }

    private function getLinesCount(): int
    {
        $count = count($this->options);
        if ($this->withQuit) {
            $count++;
        }
        return $count;
    }

    private function getLines(): array
    {
        $result = $this->options;
        if ($this->withQuit) {
            $result[] = 'Quit';
        }
        return $result;
    }
}
