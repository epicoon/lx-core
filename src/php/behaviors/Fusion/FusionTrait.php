<?php

namespace lx;

/**
 * Implementation for \lx\FusionInterface
 *
 * Trait FusionTrait
 * @package lx
 */
trait FusionTrait
{
	/** @var FusionComponentList */
	protected $fusionComponentList;

	/**
	 * @param array $list
	 * @param array $defaults
	 */
	public function initFusionComponents($list, $defaults = [])
	{
		$allDefaults = ArrayHelper::mergeRecursiveDistinct(
			$defaults,
			$this->getDefaultFusionComponents()
		);

		$this->fusionComponentList = new FusionComponentList($this);
		$this->fusionComponentList->load($list ?? [], $allDefaults);
	}

    /**
     * @param string $name
     * @return bool
     */
	public function hasFusionComponent($name)
    {
        return $this->fusionComponentList->has($name);
    }

	/**
	 * @magic __get
	 * @param string $name
	 * @return FusionComponentInterface|null
	 */
	public function getFusionComponent($name)
	{
		if ($this->fusionComponentList && $this->fusionComponentList->has($name)) {
			return $this->fusionComponentList->$name;
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function getDefaultFusionComponents()
	{
		return [];
	}
}
