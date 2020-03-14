<?php

namespace lx;

/**
 * Class EventManager
 * @package lx
 */
class EventManager extends BaseObject implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var array */
	private $list;

	/**
	 * EventManager constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
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
