<?php

namespace lx;

interface FusionInterface
{
	public function initFusionComponents(array $list, array $defaults = []): void;
    public function hasFusionComponent(string $name): bool;
	public function getFusionComponent(string $name): ?FusionComponentInterface;
	public function getDefaultFusionComponents(): array;
}
