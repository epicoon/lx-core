<?php

namespace lx;

interface HtmlTemplateProviderInterface
{
    /**
     * @param string|int $templateType
     */
    public function getTemplatePath($templateType): string;
}
