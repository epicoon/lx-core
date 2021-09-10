<?php

namespace lx;

class CliArgumentsList
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
     * @return mixed
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
