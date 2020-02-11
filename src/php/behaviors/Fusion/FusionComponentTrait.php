<?php

namespace lx;

trait FusionComponentTrait
{
	private $_owner;

	public function initAsFusionComponent($config = [])
	{
		// pass
	}

	/**
	 * @magic __construct
	 * @param array $config
	 */
	public function constructFusionComponent($config = [])
	{
		$this->_owner = $config['__fusion__'] ?? null;

		foreach ($config as $key => $value) {
			if (ClassHelper::publicPropertyExists($this, $key)
				|| ClassHelper::protectedPropertyExists($this, $key)
			) {
				$this->$key = $value;
			}
		}

		$this->initAsFusionComponent($config);
	}

	/**
	 * @magic __get
	 * @param $name
	 * @return |null
	 */
	public function getFusionComponentProperty($name)
	{
		if ($name == 'owner') {
			return $this->_owner;
		}

		if (ClassHelper::publicPropertyExists($this, $name)) {
			return $this->$name;
		}

		return null;
	}
}
