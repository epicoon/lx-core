<?php

namespace lx;

interface DbConnectorInterface
{
    public function getConnectionClassesMap(): array;
    public function hasConnection(string $connectionKey): bool;
    public function getConnection(?string $connectionKey = null): ?DbConnectionInterface;
    public function getMainConnection(): ?DbConnectionInterface;
    public function getReplicaConnection(): ?DbConnectionInterface;
    public function closeConnection(?string $connectionKey = null): void;
}
