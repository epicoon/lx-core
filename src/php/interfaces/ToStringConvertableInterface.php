<?php

namespace lx;

/**
 * Interface ToStringConvertableInterface
 * @package lx
 */
interface ToStringConvertableInterface
{
	/**
	 * @param $callback null|callable
	 * @return string
	 */
	public function toString($callback = null);
}
