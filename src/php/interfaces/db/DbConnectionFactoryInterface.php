<?php

namespace lx;

interface DbConnectionFactoryInterface
{
    public function getConnection(string $driver, array $settings): ?DbConnectionInterface;
    public function getQueryBuilder(string $driver): ?DbQueryBuilderInterface;
    public function getConnectionClass(string $driver): ?string;
    public function getQueryBuilderClass(string $driver): ?string;
}
