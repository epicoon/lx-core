<?php

namespace lx;

class CommandArgument
{
    const TYPE_ANY = 'free';
    const TYPE_INTEGER = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_STRING = 'string';
    const TYPE_PASSWORD = 'password';
    const TYPE_FLAG = 'flag';
    const TYPE_ENUM = 'enum';

    const PROBLEM_NO = 0;
    const PROBLEM_REQUIRED = 1;
    const PROBLEM_TYPE_MISMATCH = 2;
    const PROBLEM_ENUM_MISMATCH = 3;

    private array $keys;
    private string $type;
    private ?array $enum;
    private string $description;
    private bool $isMandatory;
    private bool $useInput;
    /** @var array|callable|null */
    private $selectOptions = null;
    private bool $isFlag;

    public static function service(): CommandArgument
    {
        return (new CommandArgument())
            ->setKeys(['service', 's', 0])
            ->setType(CommandArgument::TYPE_STRING)
            ->setDescription('Service name');
    }

    public static function plugin(): CommandArgument
    {
        return (new CommandArgument())
            ->setKeys(['plugin', 'p', 0])
            ->setType(CommandArgument::TYPE_STRING)
            ->setDescription('Plugin name');
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function any($keys = null): CommandArgument
    {
        $arg = new CommandArgument();
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function integer($keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setType(self::TYPE_INTEGER);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function float($keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setType(self::TYPE_FLOAT);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function string($keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setType(self::TYPE_STRING);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function password($keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setType(self::TYPE_PASSWORD);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param null|string|string[] $keys
     */
    public static function flag($keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setType(self::TYPE_FLAG);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    /**
     * @param array|callable $enum
     * @param null|string|string[] $keys
     */
    public static function enum($enum, $keys = null): CommandArgument
    {
        $arg = (new CommandArgument())->setEnum($enum);
        if ($keys !== null) {
            $arg->setKeys((array)$keys);
        }
        return $arg;
    }

    public function __construct()
    {
        $this->keys = [];
        $this->type = self::TYPE_ANY;
        $this->enum = null;
        $this->description = 'Description is not defined';
        $this->isMandatory = false;
        $this->useInput = false;
        $this->isFlag = false;
    }

    public function setKey(string $key): CommandArgument
    {
        $this->keys = [$key];
        return $this;
    }

    public function setKeys(array $key): CommandArgument
    {
        $this->keys = $key;
        return $this;
    }

    public function setType(string $type): CommandArgument
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param array|callable $enum
     */
    public function setEnum($enum): CommandArgument
    {
        $this->type = self::TYPE_ENUM;
        if (is_array($enum)) {
            $this->enum = $enum;
        } elseif (is_callable($enum)) {
            $this->enum = $enum();
        }
        return $this;
    }

    public function setDescription(string $text): CommandArgument
    {
        $this->description = $text;
        return $this;
    }

    public function setMandatory(): CommandArgument
    {
        $this->isMandatory = true;
        return $this;
    }

    public function useInput(): CommandArgument
    {
        $this->useInput = true;
        return $this;
    }

    /**
     * @param array|callable $options
     */
    public function useSelect($options): CommandArgument
    {
        $this->selectOptions = $options;
        return $this;
    }

    public function getSelectOptions(): array
    {
        if (!$this->selectOptions) {
            return [];
        }

        return is_callable($this->selectOptions)
            ? ($this->selectOptions)()
            : $this->selectOptions;
    }

    public function setFlag(): CommandArgument
    {
        $this->isFlag = true;
        return $this;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function getType(): string
    {
        if ($this->isFlag) {
            return self::TYPE_FLAG;
        }

        if ($this->enum) {
            return self::TYPE_ENUM;
        }

        return $this->type;
    }

    public function getEnum(): ?array
    {
        return $this->enum;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isMandatory(): bool
    {
        return $this->isMandatory;
    }

    public function withInput(): bool
    {
        return $this->useInput;
    }

    public function withSelect(): bool
    {
        return $this->selectOptions !== null;
    }

    public function isFlag(): bool
    {
        return $this->isFlag;
    }

    /**
     * @param mixed $value
     */
    public function validateValue($value): int
    {
        if ($value === null) {
            return $this->isMandatory()
                ? self::PROBLEM_REQUIRED
                : self::PROBLEM_NO;
        }

        if ($this->isFlag && $value !== true) {
            return self::PROBLEM_TYPE_MISMATCH;
        }

        if ($this->enum) {
            if (in_array($value, $this->enum, true)) {
                return self::PROBLEM_NO;
            } else {
                return self::PROBLEM_ENUM_MISMATCH;
            }
        }

        switch ($this->type) {
            case self::TYPE_ANY: return self::PROBLEM_NO;

            case self::TYPE_INTEGER:
            case self::TYPE_FLOAT:
            case self::TYPE_STRING:
            case self::TYPE_PASSWORD:
                //TODO
                return self::PROBLEM_NO;
        }

        return self::PROBLEM_NO;
    }
}
