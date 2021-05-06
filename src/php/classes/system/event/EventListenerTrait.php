<?php

namespace lx;

trait EventListenerTrait
{
	private EventManager $eventManager;

	public function constructEventListener(?EventManager $eventManager = null): void
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

	public static function getEventHandlersMap(): array
	{
		return [];
	}

	public function subscribe(string $eventName): void
	{
		if (!$this->eventManager) {
			return;
		}

		$this->eventManager->subscribe($eventName, $this);
	}

	public function trigger(string $eventName, array $params = []): void
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
