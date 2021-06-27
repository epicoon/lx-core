<?php

namespace lx;

class DbConnector implements FusionComponentInterface, DbConnectorInterface
{
    use FusionComponentTrait;

    const DEFAULT_DB_CONNECTION_KEY = 'default';
    const MAIN_DB_CONNECTION_KEY = 'main';
    const REPLICA_DB_CONNECTION_KEY = 'replica';

    const POSTGRESQL = 'pgsql';
    const MYSQL = 'mysql';

    private static ?DbConnectionRegistry $connectionsReestr = null;

    private array $config = [];
    private array $connectionClassesMap = [];
    private array $connections = [];

    public function __construct(array $config = [])
    {
        $config = $this->__objectConstruct($config);

        if (array_key_exists('connectionClassesMap', $config)) {
            $this->connectionClassesMap = $config['connectionClassesMap'];
            unset($config['connectionClassesMap']);
        } else {
            $this->connectionClassesMap = [
                self::POSTGRESQL => DbPostgres::class,
                self::MYSQL => DbMysql::class,
            ];
        }

        if (array_key_exists('driver', $config)) {
            if (array_key_exists($config['driver'], $this->connectionClassesMap)) {
                $this->config[self::DEFAULT_DB_CONNECTION_KEY] = $config;
            }
        } else {
            foreach ($config as $key => $value) {
                if (array_key_exists(($value['driver'] ?? null), $this->connectionClassesMap)) {
                    $this->config[$key] = $value;
                }
            }
        }
    }

    public function getConnectionClassesMap(): array
    {
        return $this->connectionClassesMap;
    }

    public function hasConnection(string $connectionKey): bool
    {
        return array_key_exists($connectionKey, $this->config);
    }

    public function getConnection(?string $connectionKey = null): ?DbConnectionInterface
    {
        if ($connectionKey === null) {
            if (array_key_exists(self::DEFAULT_DB_CONNECTION_KEY, $this->config)) {
                $connectionKey = self::DEFAULT_DB_CONNECTION_KEY;
            } elseif (array_key_exists(self::MAIN_DB_CONNECTION_KEY, $this->config)) {
                $connectionKey = self::MAIN_DB_CONNECTION_KEY;
            }
        }

        if ($connectionKey === null) {
            return null;
        }

        if (!array_key_exists($connectionKey, $this->config)) {
            \lx::devLog(['_' => [__FILE__, __CLASS__, __METHOD__, __LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT & DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "There are no settings for connection to '$connectionKey'",
            ]);
            return null;
        }

        if (!array_key_exists($connectionKey, $this->connections)) {
            if (!$this->createConnection($connectionKey)) {
                return null;
            }
        }

        return $this->connections[$connectionKey];
    }

    public function getMainConnection(): ?DbConnectionInterface
    {
        if ($this->hasConnection(self::MAIN_DB_CONNECTION_KEY)) {
            return $this->getConnection(self::MAIN_DB_CONNECTION_KEY);
        }

        return $this->getConnection(self::DEFAULT_DB_CONNECTION_KEY);
    }

    public function getReplicaConnection(): ?DbConnectionInterface
    {
        if ($this->hasConnection(self::REPLICA_DB_CONNECTION_KEY)) {
            return $this->getConnection(self::REPLICA_DB_CONNECTION_KEY);
        }

        return $this->getConnection(self::DEFAULT_DB_CONNECTION_KEY);
    }

    public function closeConnection(?string $connectionKey = null): void
    {
        if ($connectionKey === null) {
            if (array_key_exists(self::DEFAULT_DB_CONNECTION_KEY, $this->config)) {
                $connectionKey = self::DEFAULT_DB_CONNECTION_KEY;
            } elseif (array_key_exists(self::MAIN_DB_CONNECTION_KEY, $this->config)) {
                $connectionKey = self::MAIN_DB_CONNECTION_KEY;
            }
        }

        if ($connectionKey === null || !array_key_exists($connectionKey, $this->connections)) {
            return;
        }

        $registry = self::getConnectionsRegistry();
        if ($registry->drop($this->config[$connectionKey])) {
            $this->connections[$connectionKey]->close();
            unset($this->connections[$connectionKey]);
        }
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function createConnection(string $connectionKey): bool
    {
        $connectionSettings = $this->config[$connectionKey];
        
        $registry = self::getConnectionsRegistry();
        $connectionSettings['connector'] = $this;
        $connection = $registry->add($connectionSettings);
        if (!$connection) {
            return false;
        }

        $this->connections[$connectionKey] = $connection;
        return true;
    }

    private static function getConnectionsRegistry(): DbConnectionRegistry
    {
        if (self::$connectionsReestr === null) {
            self::$connectionsReestr = new DbConnectionRegistry();
        }

        return self::$connectionsReestr;
    }
}
