<?php

namespace lx;

/**
 * Interface EventListenerInterface
 * @package lx
 */
interface EventListenerInterface
{
	/**
	 * @param EventManager $eventManager
	 */
	public function constructEventListener($eventManager);

	/**
	 * @return array
	 */
	public static function getEventHandlersMap();

	/**
	 * @param string $eventName
	 */
	public function subscribe($eventName);

	/**
	 * @param string $eventName
	 * @param array $params
	 */
	public function trigger($eventName, $params = []);
}
