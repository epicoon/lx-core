<?php

namespace lx;

/**
 * Interface FusionComponentInterface
 * @package lx
 */
interface FusionComponentInterface
{
	/**
	 * @param array $config
	 */
	public function constructFusionComponent($config = []);

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getFusionComponentProperty($name);
}
