<?php

namespace lx;

interface FusionInterface extends ObjectInterface
{
    public function initFusionComponents(array $list): void;
    public function hasFusionComponent(string $name): bool;
	public function getFusionComponent(string $name): ?FusionComponentInterface;
    public function getFusionComponentTypes(): array;
    public function getDefaultFusionComponents(): array;
}
