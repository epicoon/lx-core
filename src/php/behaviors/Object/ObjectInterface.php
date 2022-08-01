<?php

namespace lx;

interface ObjectInterface
{
    public static function construct(iterable $config = [], iterable $dependences = []): ObjectInterface;
    public static function getDependenciesConfig(): array;
    public static function getDependenciesDefaultMap(): array;
    public static function isSingleton(): bool;
}
