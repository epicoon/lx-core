<?php

namespace lx;

/**
 * Class DbConnector
 * @package lx
 */
class DbConnector implements FusionComponentInterface, DbConnectorInterface
{
    use ObjectTrait;
	use FusionComponentTrait;

	const DEFAULT_DB_CONNECTION_KEY = 'db';
	const MAIN_DB_CONNECTION_KEY = 'main';
	const REPLICA_DB_CONNECTION_KEY = 'replica';

	/** @var array */
	private $config = [];

	/** @var array */
	private $connections = [];

	public function __construct($config = [])
	{
	    $this->__objectConstruct($config);
		$this->config = $config;
	}

    /**
     * @param string $db
     * @return bool
     */
    public function hasConnection($db)
    {
        return array_key_exists($db, $this->config);
    }

    /**
     * @param string|null $dbKey
     * @return string|null
     */
    public function getConnectionKey($dbKey = null)
    {
        if ($dbKey === null) {
            $dbKey = ($this->hasConnection(self::MAIN_DB_CONNECTION_KEY))
                ? self::MAIN_DB_CONNECTION_KEY
                : self::DEFAULT_DB_CONNECTION_KEY;
        }

        if (!array_key_exists($dbKey, $this->config)) {
            return null;
        }

        $settings = $this->config[$dbKey];
        return $settings['hostname'] . '_' . $settings['username'] . '_' . $settings['dbName'];
    }

	/**
	 * @param string $db
	 * @return DB|null
	 */
	public function getConnection($db = null)
	{
	    if ($db === null) {
	        $db = self::DEFAULT_DB_CONNECTION_KEY;
        }

		if (!array_key_exists($db, $this->connections)) {
			if (!array_key_exists($db, $this->config)) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "There are no settings for connection to DB '$db'",
				]);
				return null;
			}

			$dbConfig = $this->config[$db];
			$connection = DB::create($dbConfig);
			if (!$connection) {
				return null;
			}

			$connection->connect();
			$this->connections[$db] = $connection;
		}

		return $this->connections[$db];
	}

    /**
     * @return DB|null
     */
	public function getMainConnection()
    {
        if ($this->hasConnection(self::MAIN_DB_CONNECTION_KEY)) {
            return $this->getConnection(self::MAIN_DB_CONNECTION_KEY);
        }

        return $this->getConnection(self::DEFAULT_DB_CONNECTION_KEY);
    }

    /**
     * @return DB|null
     */
    public function getReplicaConnection()
    {
        if ($this->hasConnection(self::REPLICA_DB_CONNECTION_KEY)) {
            return $this->getConnection(self::REPLICA_DB_CONNECTION_KEY);
        }

        return $this->getConnection(self::DEFAULT_DB_CONNECTION_KEY);
    }

	/**
	 * @param string $db
	 */
	public function closeConnection($db = null)
	{
        if ($db === null) {
            $db = self::DEFAULT_DB_CONNECTION_KEY;
        }

		if (isset($this->connections[$db])) {
			$this->connections[$db]->close();
			unset($this->connections[$db]);
		}
	}
}
