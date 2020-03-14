<?php

namespace lx;

/**
 * Interface ContextTreeInterface
 * @package lx
 */
interface ContextTreeInterface
{
	/**
	 * @magic __construct
	 * @param array|ContextTreeInterface|null $config
	 */
	public function constructContextTree($config = null);

	/**
	 * @return ContextTreeInterface
	 */
	public function getHead();

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @param string $key
	 */
	public function setKey($key);

	/**
	 * @return ContextTreeInterface|null
	 */
	public function getParent();

	/**
	 * @param ContextTreeInterface $parent
	 */
	public function setParent($parent);

	/**
	 * @return array
	 */
	public function getNested();

	/**
	 * @return bool
	 */
	public function isHead();

	/**
	 * @return ContextTreeInterface
	 */
	public function add();

	/**
	 * @param callable $func
	 */
	public function eachContext($func);
}
