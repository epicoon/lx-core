<?php

namespace lx;

trait FusionTrait
{
	protected ?FusionComponentList $fusionComponentList = null;

	public function initFusionComponents(array $list, array $defaults = []): void
	{
		$allDefaults = ArrayHelper::mergeRecursiveDistinct(
			$defaults,
			$this->getDefaultFusionComponents()
		);

		$this->fusionComponentList = new FusionComponentList($this);
		$this->fusionComponentList->load($list, $allDefaults);
	}

	public function hasFusionComponent(string $name): bool
    {
        if (!$this->fusionComponentList) {
            return false;
        }
        
        return $this->fusionComponentList->has($name);
    }

	/**
	 * @magic __get
	 */
	public function getFusionComponent(string $name): ?FusionComponentInterface
	{
		if ($this->fusionComponentList && $this->fusionComponentList->has($name)) {
			return $this->fusionComponentList->$name;
		}

		return null;
	}

	public function getDefaultFusionComponents(): array
	{
		return [];
	}
}
