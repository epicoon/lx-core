<?php

namespace lx;

/**
 * Class CliArgumentsList
 * @package lx
 */
class CliArgumentsList
{
    /** @var array */
    private $data;
    
    /** @var bool */
    private $validated;

    /**
     * CliArgumentsList constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        $this->data = $data;
        $this->validated = false;
    }

    /**
     * @param array $data
     */
    public function setValidatedData($data)
    {
        $this->data = [];
        foreach ($data as $item) {
            foreach ($item['keys'] as $key) {
                $this->data[$key] = &$item['value'];
            }
        }
        
        $this->validated = true;
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        if (!$this->data) {
            return true;
        }

        return empty($this->data);
    }

    /**
     * @param string|int|array $key
     * @return bool
     */
    public function has($key)
    {
        $keys = (array)$key;
        foreach ($keys as $one) {
            return array_key_exists($one, $this->data);
        }
    }

    /**
     * @param string|int $key
     * @return mixed|null
     */
    public function get($key)
    {
        $keys = (array)$key;
        foreach ($keys as $one) {
            if (array_key_exists($one, $this->data)) {
                return $this->data[$one];
            }
        }
    }
}
