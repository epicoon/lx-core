<?php

namespace lx;

class ApplicationComponent implements FusionComponentInterface {
	use FusionComponentTrait;

	public function __construct($owner, $config = [])
	{
		$this->constructFusionComponent($owner, $config);
	}

	public function __get($name)
	{
		if ($name == 'app') {
			return $this->owner;
		}

		return $this->getFusionComponentProperty($name);
	}
}
