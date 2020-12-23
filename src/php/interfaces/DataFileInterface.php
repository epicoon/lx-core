<?php

namespace lx;

/**
 * Interface DataFileInterface
 * @package lx
 */
interface DataFileInterface
{
	/**
	 * @return bool
	 */
	public function exists();

	/**
	 * @return array
	 */
	public function get();

    /**
     * @return string
     */
    public function getText();

	/**
	 * @param array $data
	 * @param int $style
	 * @return File|false
	 */
	public function put($data, $style);

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param array|string|null $group
	 * @return File|false
	 */
	public function insertParam($name, $value, $group = null, $style = null);
}
