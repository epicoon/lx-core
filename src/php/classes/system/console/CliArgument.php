<?php

namespace lx;

/**
 * Class CliArgument
 * @package lx
 */
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

    /** @var array|null */
    private $key;

    /** @var string */
    private $type;

    /** @var array|null */
    private $enum;

    /** @var string */
    private $description;

    /** @var bool */
    private $isMandatory;

    /** @var bool */
    private $isFlag;

    /**
     * CliArgument constructor.
     */
    public function __construct()
    {
        $this->key = null;
        $this->type = self::TYPE_FREE;
        $this->enum = null;
        $this->description = 'Description not defined';
        $this->isMandatory = false;
        $this->isFlag = false;
    }

    /**
     * @param string|integer|array $key
     * @return $this
     */
    public function setKey($key)
    {
        $this->key = (array)$key;
        return $this;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param array $enum
     * @return $this
     */
    public function setEnum($enum)
    {
        $this->enum = $enum;
        return $this;
    }

    /**
     * @param string $text
     */
    public function setDescription($text)
    {
        $this->description = $text;
        return $this;
    }

    /**
     * @return $this
     */
    public function setMandatory()
    {
        $this->isMandatory = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function setFlag()
    {
        $this->isFlag = true;
        return $this;
    }

    /**
     * @return string|integer|array
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getType()
    {
        if ($this->isFlag) {
            return self::TYPE_FLAG;
        }

        if ($this->enum) {
            return self::TYPE_ENUM;
        }

        return $this->type;
    }

    /**
     * @return array
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->isMandatory;
    }

    /**
     * @return bool
     */
    public function isFlag()
    {
        return $this->isFlag;
    }

    /**
     * @param $value
     */
    public function validateValue($value)
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
