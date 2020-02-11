<?php

namespace lx;

/**
 * Trait ApplicationToolTrait
 * @package lx
 *
 * @property AbstractApplication $app
 */
trait ApplicationToolTrait
{
	/**
	 * @magic __get
	 * @param $name
	 * @return AbstractApplication|null
	 */
	protected function getApplicationToolProperty($name)
	{
		if ($name == 'app') {
			return \lx::$app;
		}

		return null;
	}
}
