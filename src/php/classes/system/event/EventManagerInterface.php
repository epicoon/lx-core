<?php

namespace lx;

interface EventManagerInterface
{
    public function subscribe(string $eventName, EventListenerInterface $listener): void;
    public function trigger(string $eventName, array $params = []): void;
}
