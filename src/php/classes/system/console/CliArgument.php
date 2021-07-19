<?php

namespace lx;

class CliArgument
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

    public function __construct()
    {
        $this->keys = [];
        $this->type = self::TYPE_FREE;
        $this->enum = null;
        $this->description = 'Description does not defined';
        $this->isMandatory = false;
        $this->isFlag = false;
    }

    public function setKeys(array $key): CliArgument
    {
        $this->keys = $key;
        return $this;
    }

    public function setType(string $type): CliArgument
    {
        $this->type = $type;
        return $this;
    }

    public function setEnum(array $enum): CliArgument
    {
        $this->enum = $enum;
        return $this;
    }

    public function setDescription(string $text): CliArgument
    {
        $this->description = $text;
        return $this;
    }

    public function setMandatory(): CliArgument
    {
        $this->isMandatory = true;
        return $this;
    }

    public function setFlag(): CliArgument
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
