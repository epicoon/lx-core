<?php

namespace lx;

/**
 * @property HttpApplication|ConsoleApplication|ProcessApplication $app
 */
trait ApplicationToolTrait
{
	/**
	 * @magic __get
	 */
	protected function getApplicationToolProperty(string $name): ?AbstractApplication
	{
		if ($name == 'app') {
			return \lx::$app;
		}

		return null;
	}
}
