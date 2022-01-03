<?php

namespace lx;

interface FileLinkInterface extends CommonFileInterface
{
    public function create(CommonFileInterface $file): void;
    public function getFile(): ?CommonFileInterface;
}
