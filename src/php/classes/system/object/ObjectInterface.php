<?php

namespace lx;

interface ObjectInterface
{
    public static function getDependenciesConfig(): array;
    public static function getDependenciesDefaultMap(): array;
    public static function isSingleton(): bool;
    /**
     * @param mixed$value
     */
    public function initDependency(string $name, $value): void;
}
