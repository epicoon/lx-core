<?php

namespace lx;

/**
 * Implementation for \lx\FusionComponentInterface
 *
 * Trait FusionComponentTrait
 * @package lx
 *
 * @property mixed $owner
 */
trait FusionComponentTrait
{
	/** @var FusionInterface */
	private $_owner;

	/**
	 * @magic __construct
	 * @param array $config
	 */
	public function constructFusionComponent($config = [])
	{
		$this->_owner = $config['__fusion__'] ?? null;
		foreach ($config as $key => $value) {
		    if (property_exists($this, $key)) {
                $this->$key = $value;
            }
		}
	}

	/**
	 * @magic __get
	 * @param string $name
	 * @return mixed
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
