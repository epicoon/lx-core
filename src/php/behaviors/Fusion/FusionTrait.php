<?php

namespace lx;

trait FusionTrait
{
	protected $fusionComponentList;

	public function initFusionComponents($list, $defaults = [])
	{
		$allDefaults = ArrayHelper::mergeRecursiveDistinct(
			$defaults,
			$this->getFusionComponentsDefaultConfig()
		);

		$this->fusionComponentList = new ComponentList($this);
		$this->fusionComponentList->load($list ?? [], $allDefaults);
	}

	public function getFusionComponent($name)
	{
		if ($this->fusionComponentList && $this->fusionComponentList->has($name)) {
			return $this->fusionComponentList->$name;
		}

		return null;
	}

	public function getFusionComponentsDefaultConfig()
	{
		return [];
	}
}
