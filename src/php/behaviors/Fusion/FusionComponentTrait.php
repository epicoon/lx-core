<?php

namespace lx;

/**
 * @see FusionComponentInterface
 *
 * @property FusionInterface|null $owner
 */
trait FusionComponentTrait
{
    use ObjectTrait;
    
	private ?FusionInterface $_owner = null;

	/**
	 * @magic __construct
	 */
	public function constructFusionComponent(array &$config)
	{
		$this->_owner = $config['__fusion__'] ?? null;
        
        unset($config['__fusion__']);
		foreach ($config as $key => $value) {
		    if (property_exists($this, $key)) {
                $this->$key = $value;
            }
		}
	}

	/**
	 * @magic __get
	 * @return mixed
	 */
	public function getFusionComponentProperty(string $name)
	{
		if ($name == 'owner') {
			return $this->_owner;
		}

		return null;
	}
}
