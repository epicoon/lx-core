<?php

namespace lx;

/**
 * Interface LoggerInterface
 * @package lx
 */
interface LoggerInterface {
	/**
	 * @param $data
	 * @param null $category
	 * @return mixed
	 */
	public function log($data, $category = null);
}
