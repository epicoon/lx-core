<?php

namespace lx;

trait FusionComponentTrait
{
	protected $owner;

	public function __construct($owner, $config = [])
	{
		$this->constructFusionComponent($owner, $config);
	}

	public function initAsFusionComponent($config = [])
	{
		// pass
	}

	public function constructFusionComponent($owner, $config = [])
	{
		$this->owner = $owner;

		foreach ($config as $key => $value) {
			if (ClassHelper::publicPropertyExists($this, $key)
				|| ClassHelper::protectedPropertyExists($this, $key)
			) {
				$this->$key = $value;
			}
		}

		$this->initAsFusionComponent($config);
	}

	public function getFusionComponentProperty($name)
	{
		if ($name == 'owner') {
			return $this->owner;
		}

		if (ClassHelper::publicPropertyExists($this, $name)) {
			return $this->$name;
		}

		return null;
	}
}
