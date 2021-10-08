<?php

namespace lx;

trait FusionTrait
{
	protected ?FusionComponentList $fusionComponentList = null;

	public function initFusionComponents(array $list/*, array $defaults = []*/): void
	{
		$this->fusionComponentList = new FusionComponentList($this);
        $this->fusionComponentList->load($list, $this->getDefaultFusionComponents());
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

    public function getFusionComponentTypes(): array
    {
        return [];
    }

	public function getDefaultFusionComponents(): array
	{
		return [];
	}
}
