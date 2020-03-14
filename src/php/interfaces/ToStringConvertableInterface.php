<?php

namespace lx;

/**
 * Interface ToStringConvertableInterface
 * @package lx
 */
interface ToStringConvertableInterface
{
	/**
	 * @param callable $callback
	 * @return string
	 */
	public function toString($callback = null);

	/**
	 * @return string
	 */
	public function __toString();
}
