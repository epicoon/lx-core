<?php

namespace lx;

/**
 * Interface DataFileInterface
 * @package lx
 */
interface DataFileInterface extends FileInterface
{
    /**
     * @return mixed
     */
    public function get();

    /**
     * @return string
     */
    public function getText();

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param array|string|null $group
	 * @return File|false
	 */
	public function insertParam($name, $value, $group = null, $style = null);
}
