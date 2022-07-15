<?php

namespace lx;

use lx;

class DbConnector implements FusionComponentInterface, DbConnectorInterface
{
    use FusionComponentTrait;

    const DEFAULT_DB_CONNECTION_KEY = 'default';
    const MAIN_DB_CONNECTION_KEY = 'main';
    const REPLICA_DB_CONNECTION_KEY = 'replica';

    private static ?DbConnectionRegistry $connectionsReestr = null;
    private ?DbConnectionFactoryInterface $connectionFactory = null;
    private array $config = [];
    private array $connections = [];

    protected function afterObjectConstruct(iterable $config): void
    {
        if (array_key_exists('driver', $config)) {
            $this->config[self::DEFAULT_DB_CONNECTION_KEY] = $config;
        } else {
            foreach ($config as $key => $value) {
                if (array_key_exists('driver', $value)) {
                    $this->config[$key] = $value;
                }
            }
        }
    }

    public function getConnectionFactory(): DbConnectionFactoryInterface
    {
        if ($this->connectionFactory === null) {
            $this->connectionFactory = lx::$app->diProcessor->build()
                ->setInterface(DbConnectionFactoryInterface::class)
                ->setDefaultClass(DbConnectionFactory::class)
                ->getInstance();
        }

        return $this->connectionFactory;
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
