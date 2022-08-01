<?php

namespace lx;

interface ResourceContextInterface
{
    public function setParams(array $params): void;
    public function invoke(): void;
}
