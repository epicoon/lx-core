<?php

namespace lx;

/**
 * Trait EventListenerTrait
 * @package lx
 */
trait EventListenerTrait
{
	/** @var EventManager */
	private $eventManager;

	/**
	 * @magic __construct
	 * @param EventManager $eventManager
	 */
	public function constructEventListener($eventManager = null)
	{
		if ($eventManager instanceof EventManager) {
			$this->eventManager = $eventManager;
		} elseif (
			is_array($eventManager)
			&& array_key_exists('eventManager', $eventManager)
			&& $eventManager['eventManager'] instanceof EventManager) {
			$this->eventManager = $eventManager['eventManager'];
		} else {
			$this->eventManager = \lx::$app->events;
		}
		$map = static::getEventHandlersMap();
		foreach (array_keys($map) as $eventName) {
			$this->subscribe($eventName);
		}
	}

	/**
	 * @return array
	 */
	public static function getEventHandlersMap()
	{
		return [];
	}

	/**
	 * @param string $eventName
	 */
	public function subscribe($eventName)
	{
		if (!$this->eventManager) {
			return;
		}

		$this->eventManager->subscribe($eventName, $this);
	}

	/**
	 * @param string $eventName
	 * @param array $params
	 */
	public function trigger($eventName, $params = [])
	{
		$map = static::getEventHandlersMap();

		$methodName = array_key_exists($eventName, $map)
			? $map[$eventName]
			: $eventName;

		if (!method_exists($this, $methodName)) {
			return;
		}

		if (!is_array($params)) {
			$params = [$params];
		}
		call_user_func_array([$this, $methodName], $params);
	}
}
