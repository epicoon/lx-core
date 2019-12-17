<?php

namespace lx;

/**
 * Class ErrorCollectorList
 * @package lx
 */
class ErrorCollectorList
{
	/** @var array */
	private $list = [];

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return !empty($this->list);
	}

	/**
	 * @param $errorInfo string|array
	 */
	public function addError($errorInfo)
	{
		$this->list[] = new ErrorCollectorError($errorInfo);
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->list;
	}

	/**
	 * @return ErrorCollectorError|null
	 */
	public function getFirstError()
	{
		return $this->list[0] ?? null;
	}
}
