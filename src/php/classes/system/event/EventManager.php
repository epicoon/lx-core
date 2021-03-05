<?php

namespace lx;

/**
 * Class EventManager
 * @package lx
 */
class EventManager implements FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	private array $list;

	public function __construct(array $config = [])
	{
	    $this->__objectConstruct($config);
		$this->list = [];
	}

	/**
	 * @param string $eventName
	 * @param array $params
	 */
	public function trigger($eventName, $params = [])
	{
		if (!array_key_exists($eventName, $this->list)) {
			return;
		}

		$list = $this->list[$eventName];
		foreach ($list as $listener) {
			$listener->trigger($eventName, $params);
		}
	}

	/**
	 * @param string $eventName
	 * @param EventListenerInterface $listener
	 */
	public function subscribe($eventName, $listener)
	{
		if (!array_key_exists($eventName, $this->list)) {
			$this->list[$eventName] = [];
		}

		$this->list[$eventName][] = $listener;
	}
}
