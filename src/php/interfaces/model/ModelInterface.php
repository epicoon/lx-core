<?php

namespace lx;

interface ModelInterface
{
    //TODO return ModelIdInterface
    public function getId(): ?int;
    
    public function hasField(string $name): bool;

    /**
     * @param mixed $value
     */
    public function setField(string $name, $value): void;

    public function setFields(array $fields): void;

    //TODO попытаться избавиться от &
    /**
     * @return mixed
     */
    public function &getField(string $name);

    public function getFields(?array $names = null): array;

    public function hasRelation(string $name): bool;

    public function setRelated(string $name, ?ModelInterface $model): void;

    //TODO попытаться избавиться от &
    /**
     * @return mixed
     */
    public function &getRelated(string $name);

    public function removeRelated(string $name, ?ModelInterface $model = null): void;

    public function clearRelated(string $name): void;
}
