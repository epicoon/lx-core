<?php

namespace lx;

abstract class AbstractConsoleInput
{
    const DIRECTIVE_INPUT = 'input';
    const DIRECTIVE_ENTER = 'enter';
    const DIRECTIVE_TAB = 'tab';
    const DIRECTIVE_BACKSPACE = 'backspace';
    const DIRECTIVE_DELETE = 'delete';
    const DIRECTIVE_UP = 'up';
    const DIRECTIVE_DOWN = 'down';
    const DIRECTIVE_LEFT = 'left';
    const DIRECTIVE_RIGHT = 'right';
    const DIRECTIVE_HOME = 'home';
    const DIRECTIVE_END = 'end';

    protected ?string $hint = null;
    protected array $hintDecor = [];
    protected array $textDecor = [];
    protected array $callbacks = [];

    /**
     * @return mixed
     */
    abstract public function run();

    public function setHint(string $hint): AbstractConsoleInput
    {
        $this->hint = $hint;
        return $this;
    }

    public function setHintDecor(array $hintDecor): AbstractConsoleInput
    {
        $this->hintDecor = $hintDecor;
        return $this;
    }

    public function setTextDecor(array $textDecor): AbstractConsoleInput
    {
        $this->textDecor = $textDecor;
        return $this;
    }

    public function setCallbacks(array $callbacks): AbstractConsoleInput
    {
        $this->callbacks = $callbacks;
        return $this;
    }

    /**
     * @param callable|array $callback  if array: [object{context}, callable]
     */
    public function setCallback(string $key, $callback): AbstractConsoleInput
    {
        $this->callbacks[$key] = $callback;
        return $this;
    }

    protected function defineInput(): array
    {
        $char = stream_get_contents(STDIN, 1);
        $ord = ord($char);
        switch ($ord) {
            case 10: return [null, self::DIRECTIVE_ENTER];
            case 9: return [null, self::DIRECTIVE_TAB];
            case 127: return [null, self::DIRECTIVE_BACKSPACE];

            case 27:
                stream_get_contents(STDIN, 1);
                $ext = stream_get_contents(STDIN, 1);
                switch ($ext) {
                    case 'A': return [null, self::DIRECTIVE_UP];
                    case 'B': return [null, self::DIRECTIVE_DOWN];
                    case 'C': return [null, self::DIRECTIVE_RIGHT];
                    case 'D': return [null, self::DIRECTIVE_LEFT];
                    case 'F': return [null, self::DIRECTIVE_END];
                    case 'H': return [null, self::DIRECTIVE_HOME];
                    case '3':
                        stream_get_contents(STDIN, 1);
                        return [null, self::DIRECTIVE_DELETE];
                }

            // Cyrillic
            case 208:
            case 209:
                $char .= stream_get_contents(STDIN, 1);
        }

        return [$char, self::DIRECTIVE_INPUT];
    }

    protected function hasCallback(string $type): bool
    {
        return array_key_exists($type, $this->callbacks);
    }

    protected function runCallback(string $type): void
    {
        $callback = $this->callbacks[$type] ?? null;
        if (!$callback) {
            return;
        }

        $context = null;
        if (is_array($callback)) {
            $context = $callback[0] ?? null;
            $callback = $callback[1] ?? null;
        }

        if (!is_callable($callback)) {
            return;
        }

        if ($context === null) {
            $callback();
        } else {
            call_user_method($callback, $context);
        }
    }
}
