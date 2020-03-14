<?php

namespace lx;

/**
 * Realisation for \lx\ClassOfServiceInterface
 *
 * Trait ClassOfServiceTrait
 * @package lx
 */
trait ClassOfServiceTrait
{
	/**
	 * Get service name for current class
	 *
	 * @return string|null
	 */
	public function getServiceName()
	{
		return ClassHelper::defineServiceName(static::class);
	}

	/**
	 * Get service for current class
	 *
	 * @return lx\Service|null
	 */
	public function getService()
	{
		$name = $this->getServiceName();
		if (!$name) {
			return null;
		}

		return \lx::$app->getService($name);
	}
}
