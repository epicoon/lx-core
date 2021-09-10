<?php

namespace lx;

interface ResponseInterface
{
    public function setWarning(): void;
    public function applyResponseParams(): void;
    public function getCode(): int;
    public function isForbidden(): bool;
    /**
     * @return mixed
     */
    public function getData();
    public function getDataString(): string;
    public function getType(): string;
}
