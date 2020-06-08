<?php

namespace lx;

/**
 * Interface RendererInterface
 * @package lx
 */
interface RendererInterface
{
    /**
     * @param string $template
     * @param array $params
     * @return string
     */
    public function render($template, $params = []);
}
