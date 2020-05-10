<?php

namespace lx;

/**
 * Trait ApplicationToolTrait
 * @package lx
 *
 * @property HttpApplication|ConsoleApplication|ProcessApplication $app
 */
trait ApplicationToolTrait
{
	/**
	 * @magic __get
	 * @param $name
	 * @return HttpApplication|ConsoleApplication|ProcessApplication|null
	 */
	protected function getApplicationToolProperty($name)
	{
		if ($name == 'app') {
			return \lx::$app;
		}

		return null;
	}
}
