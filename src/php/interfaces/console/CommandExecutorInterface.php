<?php

namespace lx;

interface CommandExecutorInterface
{
    public function setParams(array $params): void;

    /**
     * @return mixed
     */
    public function exec();
}
