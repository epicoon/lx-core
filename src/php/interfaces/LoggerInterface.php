<?php

namespace lx;

/**
 * Interface LoggerInterface
 * @package lx
 */
interface LoggerInterface {
	/**
	 * @param mixed $data
	 * @param string $category
	 */
	public function log($data, $category = null);
}
