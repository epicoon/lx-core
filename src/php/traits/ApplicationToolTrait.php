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
	 * @param $name
	 * @return AbstractApplication|null
	 */
	public function __get($name) {
		return $this->ApplicationToolTrait__get($name);
	}

	/**
	 * @param $name
	 * @return AbstractApplication|null
	 */
	private function ApplicationToolTrait__get($name)
	{
		if ($name == 'app') {
			return \lx::$app;
		}

		return null;
	}
}
