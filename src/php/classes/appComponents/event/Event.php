<?php

namespace lx;

class Event
{
    private array $payload;

    public function __construct(?array $payload)
    {
        $this->payload = $payload ?? [];
    }

    /**
     * @return mixed|null
     */
    public function getPayload(?string $key = null)
    {
        if ($key === null) {
            return $this->payload;
        }
        return $this->payload[$key] ?? null;
    }
}
