<?php

namespace lx;

/**
 * Interface FusionInterface
 * @package lx
 */
interface FusionInterface
{
	/**
	 * @param array $list
	 * @param array $defaults
	 */
	public function initFusionComponents($list, $defaults = []);

	/**
	 * @param string $name
	 * @return FusionComponentInterface|null
	 */
	public function getFusionComponent($name);

	/**
	 * @return array
	 */
	public function getFusionComponentsDefaultConfig();
}
