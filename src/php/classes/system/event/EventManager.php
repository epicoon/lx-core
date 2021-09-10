<?php

namespace lx;

class EventManager implements EventManagerInterface
{
	private array $list = [];

    public function subscribe(string $eventName, EventListenerInterface $listener): void
    {
        if (!array_key_exists($eventName, $this->list)) {
            $this->list[$eventName] = [];
        }

        $this->list[$eventName][] = $listener;
    }

    /**
     * @param mixed $params
     */
	public function trigger(string $eventName, $params = null): void
	{
		if (!array_key_exists($eventName, $this->list)) {
			return;
		}

		$list = $this->list[$eventName];
		/** @var EventListenerInterface $listener */
        foreach ($list as $listener) {
			$listener->trigger($eventName, $params);
		}
	}
}
