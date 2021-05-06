<?php

namespace lx;

/**
 * Interface EventListenerInterface
 * @package lx
 */
interface EventListenerInterface
{
	public function constructEventListener(EventManager $eventManager): void;
	public static function getEventHandlersMap(): array;
	public function subscribe(string $eventName): void;
	public function trigger(string $eventName, array $params = []): void;
}
