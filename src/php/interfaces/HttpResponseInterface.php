<?php

namespace lx;

interface HttpResponseInterface
{
    public function getCode(): int;
    /**
     * @return mixed
     */
    public function getData();
    public function setCode(int $code): void;
    /**
     * @param mixed $data
     */
    public function setData($data): void;
    public function setWarning(): void;
    public function isForbidden(): bool;
    public function requireAuthorization(): bool;
    public function getDataAsString(): string;
    public function getDataType(): string;
    public function beforeSend(): void;
    public function send(): void;
}
