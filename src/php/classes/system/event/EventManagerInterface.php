<?php

namespace lx;

interface EventManagerInterface
{
    public function subscribe(string $eventName, EventListenerInterface $listener): void;
    /**
     * @param mixed $params
     */
    public function trigger(string $eventName, $params = null): void;
}
