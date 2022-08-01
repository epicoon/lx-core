<?php

namespace lx;

class DbConnectionFactory implements DbConnectionFactoryInterface
{
    const POSTGRESQL = 'pgsql';
    const MYSQL = 'mysql';

    public function getConnection(string $driver, array $settings): ?DbConnectionInterface
    {
        $class = $this->getConnectionClass($driver);
        if (!ClassHelper::exists($class)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Connection class '$class' does not exist",
            ]);
            return null;
        }

        if (!ClassHelper::implements($class, DbConnectionInterface::class)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Connection class '$class' has to implement 'lx\\DbConnectionInterface'",
            ]);
            return null;
        }

        return new $class($settings);
    }

    public function getQueryBuilder(string $driver): ?DbQueryBuilderInterface
    {
        $class = $this->getQueryBuilderClass($driver);

        if (!ClassHelper::exists($class)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Query builder class '$class' does not exist",
            ]);
            return null;
        }

        if (!ClassHelper::implements($class, DbQueryBuilderInterface::class)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Query builder class '$class' has to implement 'lx\\DbQueryBuilderInterface'",
            ]);
            return null;
        }

        return new $class();
    }

    public function getConnectionClass(string $driver): ?string
    {
        switch ($driver) {
            case self::MYSQL:
                return MysqlConnection::class;
            case self::POSTGRESQL:
                return PostgresConnection::class;
            default:
                return null;
        }
    }

    public function getQueryBuilderClass(string $driver): ?string
    {
        switch ($driver) {
            case self::MYSQL:
                return MysqlQueryBuilder::class;
            case self::POSTGRESQL:
                return PostgresQueryBuilder::class;
            default:
                return null;
        }
    }
}
