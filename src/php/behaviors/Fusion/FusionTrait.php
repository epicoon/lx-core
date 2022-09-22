<?php

namespace lx;

trait FusionTrait
{
    use ObjectTrait;
    
	protected ?FusionComponentList $fusionComponentList = null;

	public function initFusionComponents(array $list, FusionInterface $fusion = null): void
	{
        $fusion = $fusion ?? $this;
		$this->fusionComponentList = new FusionComponentList($fusion);
        $this->fusionComponentList->load($list, $fusion->getDefaultFusionComponents());
	}

	public function hasFusionComponent(string $name): bool
    {
        if (!$this->fusionComponentList) {
            return false;
        }
        
        return $this->fusionComponentList->has($name);
    }

    public function setFusionComponent(string $name, array $config): void
    {
        $this->fusionComponentList->set($name, $config);
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

    public function eachFusionComponent(callable $callback): void
    {
        foreach ($this->fusionComponentList->getNames() as $name) {
            $callback($this->getFusionComponent($name), $name);
        }
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
