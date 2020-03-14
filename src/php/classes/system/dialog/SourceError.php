<?php

namespace lx;

/**
 * Class SourceError
 * @package lx
 */
class SourceError
{
	/** @var int */
	private $code;

	/**
	 * SourceError constructor.
	 * @param int $code
	 */
	public function __construct($code)
	{
		$this->code = $code;
	}

	/**
	 * @return int
	 */
	public function getCode()
	{
		return $this->code;
	}
}
