<?php

namespace lx;

class EventManager extends Object implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	private $list;

	public	function __construct($config = [])
	{
		parent::__construct($config);
		$this->list = [];
	}

	public function trigger($eventName, $params = [])
	{
		if ( ! array_key_exists($eventName, $this->list)) {
			return;
		}

		$list = $this->list[$eventName];
		foreach ($list as $listener) {
			$listener->trigger($eventName, $params);
		}
	}

	/**
	 * @param $eventName string
	 * @param $listener EventLestenerInterface
	 */
	public function subscribe($eventName, $listener)
	{
		if ( ! array_key_exists($eventName, $this->list)) {
			$this->list[$eventName] = [];
		}

		$this->list[$eventName][] = $listener;
	}
}
