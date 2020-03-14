<?php

namespace lx;

/**
 * Class DataFileAdapter
 * @package lx
 */
abstract class DataFileAdapter
{
	/** @var File */
	protected $file;

	/**
	 * DataFileAdapter constructor.
	 * @param File $file
	 */
	public function __construct($file)
	{
		$this->file = $file;
	}

	/**
	 * @param mixed $elem
	 * @return string|null
	 */
	protected static function packPrimitiveType($elem)
	{
		if (is_numeric($elem)) {
			return $elem;
		}

		if ($elem === true) {
			return 'true';
		}

		if ($elem === false) {
			return 'false';
		}

		if ($elem === null) {
			return 'null';
		}

		return null;
	}

	/**
	 * @return array
	 */
	abstract public function parse();

	/**
	 * @param array $data
	 * @param int $style
	 * @return string
	 */
	abstract public function dataToString($data, $style);
}
