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
	public function trigger(string $eventName, Event $event): void;
}
