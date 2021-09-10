<?php

namespace lx;

interface EventListenerInterface
{
    /**
     * @param array|EventManagerInterface|null $eventManager
     */
	public function constructEventListener($eventManager = null): void;
	public static function getEventHandlersMap(): array;
	public function subscribe(string $eventName): void;
    /**
     * @param mixed $params
     */
	public function trigger(string $eventName, $params = null): void;
}
