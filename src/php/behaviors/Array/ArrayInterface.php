<?php

namespace lx;

/**
 * Interface ArrayInterface
 * @package lx
 */
interface ArrayInterface extends \ArrayAccess, \IteratorAggregate
{
	/**
	 * @param array $array
	 */
	public function constructArray($array);

	/**
	 * @return int
	 */
	public function getIndex();

	/**
	 * @return bool
	 */
	public function isAssoc();

	/**
	 * @return bool
	 */
	public function isEmpty();

	/**
	 * @return int
	 */
	public function len();

	public function clear();

	/**
	 * @return mixed
	 */
	public function pop();

	/**
	 * @return mixed
	 */
	public function shift();

	/**
	 * @return mixed
	 */
	public function getFirst();

	/**
	 * @return mixed
	 */
	public function getLast();

	/**
	 * @param mixed $value
	 * @return mixed|false
	 */
	public function getKeyByValue($value);

	/**
	 * @param mixed $value
	 * @return bool
	 */
	public function contains($value);

	/**
	 * @return array
	 */
	public function toArray();
}
