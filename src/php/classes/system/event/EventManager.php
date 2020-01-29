<?php

namespace lx;

class EventManager extends ApplicationComponent
{
	private $list;

	public	function __construct($owner, $config = [])
	{
		parent::__construct($owner, $config);
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
