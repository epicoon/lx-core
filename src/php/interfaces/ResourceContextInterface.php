<?php

namespace lx;

interface ResourceContextInterface
{
    public function setParams(array $params): void;

    /**
     * @return mixed
     */
    public function invoke();
}
