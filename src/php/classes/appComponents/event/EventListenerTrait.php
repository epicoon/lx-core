<?php

namespace lx;

trait EventListenerTrait
{
	private ?EventManagerInterface $eventManager = null;

    /**
     * @magic __construct
     * @param array|EventManagerInterface|null $eventManager
     */
	public function constructEventListener($eventManager = null): void
	{
		if ($eventManager instanceof EventManagerInterface) {
			$this->eventManager = $eventManager;
		} elseif (
			is_array($eventManager)
			&& array_key_exists('eventManager', $eventManager)
			&& $eventManager['eventManager'] instanceof EventManagerInterface) {
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

	public function trigger(string $eventName, Event $event): void
	{
		$map = static::getEventHandlersMap();

		$methodName = array_key_exists($eventName, $map)
			? $map[$eventName]
			: $eventName;

		if (!method_exists($this, $methodName)) {
			return;
		}

        $this->$methodName($event);
	}
}
