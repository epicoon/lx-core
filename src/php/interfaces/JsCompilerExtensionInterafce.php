<?php

namespace lx;

interface JsCompilerExtensionInterafce
{
    public function setConductor(ConductorInterface $conductor): void;
    public function getConductor(): ConductorInterface;
    public function beforeCutComments(string $code, ?string $filePath = null): string;
    public function afterCutComments(string $code, ?string $filePath = null): string;
}
