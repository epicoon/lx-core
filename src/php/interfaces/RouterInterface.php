<?php

namespace lx;

interface RouterInterface
{
    public function route(string $route): ?ResourceContextInterface;
    public function getAssetPrefix(): string;
}
