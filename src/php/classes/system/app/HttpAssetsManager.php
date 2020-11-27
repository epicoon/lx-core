<?php

namespace lx;

/**
 * Class HttpAssetsManager
 * @package lx
 */
class HttpAssetsManager implements FusionComponentInterface
{
    use ObjectTrait;
    use FusionComponentTrait;

    /** @var string */
    protected $colorSchemaModule = 'lx.ColorSchema.white';

    /**
     * @return string
     */
    public function getCssColorSchema()
    {
        return $this->colorSchemaModule;
    }
}
