<?php

namespace lx;

class JsCompilerExtension implements FusionComponentInterface, JsCompilerExtensionInterafce
{
    use FusionComponentTrait;

    private ConductorInterface $conductor;
    
    public function setConductor(ConductorInterface $conductor): void
    {
        $this->conductor = $conductor;
    }

    public function getConductor(): ConductorInterface
    {
        return $this->conductor;
    }
    
    public function beforeCutComments(string $code, ?string $filePath = null): string
    {
        return $code;
    }

    public function afterCutComments(string $code, ?string $filePath = null): string
    {
        return $code;
    }
}
