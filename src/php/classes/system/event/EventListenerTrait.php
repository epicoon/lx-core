<?php

namespace lx;

trait EventListenerTrait
{
	private $eventManager;

	/**
	 * @magic __construct
	 * @param EventManager $eventManager
	 */
	public function constructEventListener($eventManager = null)
	{
		if ($eventManager instanceof EventManager) {
			$this->eventManager = $eventManager;
		} else {
			$this->eventManager = \lx::$app->events;
		}
		$map = self::getEventHandlersMap();
		foreach (array_keys($map) as $eventName) {
			$this->subscribe($eventName);
		}
	}

	public function subscribe($eventName)
	{
		if ( ! $this->eventManager) {
			return;
		}

		$this->eventManager->subscribe($eventName, $this);
	}

	public function trigger($eventName, $params = [])
	{
		$map = self::getEventHandlersMap();

		$methodName = array_key_exists($eventName, $map)
			? $map[$eventName]
			: $eventName;

		if ( ! method_exists($this, $methodName)) {
			return;
		}

		if ( ! is_array($params)) {
			$params = [$params];
		}
		call_user_func_array([$this, $methodName], $params);
	}

	public static function getEventHandlersMap()
	{
		return [];
	}
}
