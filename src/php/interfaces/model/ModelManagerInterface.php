<?php

namespace lx;

interface ModelManagerInterface
{
    public function getModelSchema(string $modelName): ?ModelSchemaInterface;
}
