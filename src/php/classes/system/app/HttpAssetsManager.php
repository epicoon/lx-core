<?php

namespace lx;

class HttpAssetsManager implements FusionComponentInterface
{
    use FusionComponentTrait;

    protected string $colorSchemaModule = 'lx.ColorSchema.white';

    public function getCssColorSchema(): string
    {
        return $this->colorSchemaModule;
    }
}
