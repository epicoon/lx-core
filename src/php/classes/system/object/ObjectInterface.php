<?php

namespace lx;

interface ObjectInterface
{
    public function __construct(iterable $config = []);
    public static function construct(iterable $config = []): ObjectInterface;
    public static function getDependenciesConfig(): array;
    public static function getDependenciesDefaultMap(): array;
    public static function isSingleton(): bool;
}
