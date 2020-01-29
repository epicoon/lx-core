<?php

namespace lx;

interface EventLestenerInterface
{
	public function constructEventListener($eventManager);
	public function subscribe($eventName);
	public function trigger($eventName, $params = []);
	public static function getEventHandlersMap();
}
