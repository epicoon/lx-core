<?php

namespace lx;

/**
 * Interface ModelManagerInterface
 * @package lx
 */
interface ModelManagerInterface
{
    public function getModelSchema(string $modelName): ?ModelSchemaInterface;
}
