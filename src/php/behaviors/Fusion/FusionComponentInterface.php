<?php

namespace lx;

/**
 * @see FusionComponentTrait
 */
interface FusionComponentInterface
{
	public function constructFusionComponent(array &$config);
	/**
	 * @return mixed
	 */
	public function getFusionComponentProperty(string $name);
}
