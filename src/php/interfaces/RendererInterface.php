<?php

namespace lx;

interface RendererInterface
{
    public function render(string $template, array $params = []): string;
}
