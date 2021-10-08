<?php

namespace lx;

interface ResponseInterface
{
    public function setWarning(): void;
    public function beforeSend(): void;
    public function getCode(): int;
    public function isForbidden(): bool;
    /**
     * @return mixed
     */
    public function getData();
    public function getDataAsString(): string;
    public function getDataType(): string;
}
