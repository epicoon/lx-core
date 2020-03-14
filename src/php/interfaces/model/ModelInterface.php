<?php

namespace lx;

/**
 * Interface ModelInterface
 * @package lx
 */
interface ModelInterface
{
	/**
	 * @param mixed $data
	 */
	public function setData($data);

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasField($name);
}
