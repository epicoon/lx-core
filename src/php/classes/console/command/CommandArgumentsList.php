<?php

namespace lx;

class CommandArgumentsList
{
    private array $data = [];
    private bool $validated;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->validated = false;
    }

    public function setValidatedData(array $data): void
    {
        $this->data = [];
        foreach ($data as $item) {
            foreach ($item['keys'] as $key) {
                $this->data[$key] = &$item['value'];
            }
        }
        
        $this->validated = true;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * @param string|int|array $key
     * @return bool
     */
    public function has($key): bool
    {
        $keys = (array)$key;
        foreach ($keys as $one) {
            return array_key_exists($one, $this->data);
        }
    }

    /**
     * @param array|string|int $key
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
        return null;
    }

    /**
     * @param array|string|int $key
     * @param mixed $value
     */
    public function set($key, $value, $force = false)
    {
        $keys = (array)$key;
        foreach ($keys as $one) {
            if (array_key_exists($one, $this->data) || $force) {
                $this->data[$one] = $value;
                return;
            }
        }
    }
}
