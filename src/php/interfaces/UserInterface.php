<?php

namespace lx;

/**
 * Interface UserInterface
 * @package lx
 */
interface UserInterface
{
    public function isGuest(): bool;
    public function setModel(ModelInterface $userModel): void;
    public function getModel(): ?ModelInterface;
    public function setAuthFieldName(string $name): void;
    public function getAuthFieldName(): string;
    public function getAuthValue(): string;
}
