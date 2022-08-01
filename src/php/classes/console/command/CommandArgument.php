<?php

namespace lx;

class CommandArgument
{
    const TYPE_FREE = 'free';
    const TYPE_INTEGER = 'int';
    const TYPE_FLOAT = 'float';
    const TYPE_STRING = 'string';
    const TYPE_FLAG = 'flag';
    const TYPE_ENUM = 'enum';

    const PROBLEM_NO = 0;
    const PROBLEM_TYPE_MISMATCH = 1;
    const PROBLEM_ENUM_MISMATCH = 2;

    private array $keys;
    private string $type;
    private ?array $enum;
    private string $description;
    private bool $isMandatory;
    private bool $isFlag;

    public static function getServiceArgument(): CommandArgument
    {
        return (new CommandArgument())->setKeys(['service', 's', 0])
            ->setType(CommandArgument::TYPE_STRING)
            ->setDescription('Service name');
    }

    public static function getPluginArgument(): CommandArgument
    {
        return (new CommandArgument())->setKeys(['plugin', 'p', 0])
            ->setType(CommandArgument::TYPE_STRING)
            ->setDescription('Plugin name');
    }

    public function __construct()
    {
        $this->keys = [];
        $this->type = self::TYPE_FREE;
        $this->enum = null;
        $this->description = 'Description is not defined';
        $this->isMandatory = false;
        $this->isFlag = false;
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

    public function setEnum(array $enum): CommandArgument
    {
        $this->enum = $enum;
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

    public function isFlag(): bool
    {
        return $this->isFlag;
    }

    /**
     * @param mixed $value
     */
    public function validateValue($value): int
    {
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
            case self::TYPE_FREE: return self::PROBLEM_NO;

            case self::TYPE_INTEGER:
            case self::TYPE_FLOAT:
            case self::TYPE_STRING:
                //TODO
                return self::PROBLEM_NO;
        }

        return self::PROBLEM_NO;
    }
}
