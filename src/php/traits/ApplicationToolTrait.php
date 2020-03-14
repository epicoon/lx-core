<?php

namespace lx;

/**
 * Trait ApplicationToolTrait
 * @package lx
 *
 * @property Application|ConsoleApplication $app
 */
trait ApplicationToolTrait
{
	/**
	 * @magic __get
	 * @param $name
	 * @return Application|ConsoleApplication|null
	 */
	protected function getApplicationToolProperty($name)
	{
		if ($name == 'app') {
			return \lx::$app;
		}

		return null;
	}
}
