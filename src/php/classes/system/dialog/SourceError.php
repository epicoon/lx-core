<?php

namespace lx;

class SourceError
{
	private $code;

	public function __construct($code)
	{
		$this->code = $code;
	}

	public function getCode()
	{
		return $this->code;
	}
}
