<?php

namespace lx;

interface FusionInterface
{
    public function initFusionComponents(array $list): void;
    public function hasFusionComponent(string $name): bool;
    public function setFusionComponent(string $name, array $config): void;
	public function getFusionComponent(string $name): ?FusionComponentInterface;
    public function eachFusionComponent(callable $callback): void;
    public function getFusionComponentTypes(): array;
    public function getDefaultFusionComponents(): array;
}
