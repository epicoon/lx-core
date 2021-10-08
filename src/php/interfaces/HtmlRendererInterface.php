<?php

namespace lx;

interface HtmlRendererInterface
{
    public function setTemplateProvider(HtmlTemplateProviderInterface $provider): HtmlRenderer;
    /**
     * @param string|int $type
     */
    public function setTemplateType($type): HtmlRenderer;
    public function setTemplatePath(string $path): HtmlRenderer;
    public function setTemplateFile(FileInterface $file): HtmlRenderer;
    public function setParams(array $params): HtmlRenderer;
    public function getTemplateProvider(): HtmlTemplateProviderInterface;
    public function getTemplatePath(): ?string;
    public function render(): string;
}
