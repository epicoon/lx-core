<?php

namespace lx;

/**
 * @see FusionComponentTrait
 */
interface FusionComponentInterface extends ObjectInterface
{
	public function constructFusionComponent(array &$config);
	/**
	 * @return mixed
	 */
	public function getFusionComponentProperty(string $name);
}
