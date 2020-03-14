<?php

namespace lx;

/**
 * Class DbConnector
 * @package lx
 */
class DbConnector extends BaseObject implements FusionComponentInterface
{
	use FusionComponentTrait;

	const DEFAULT_DB_CONNECTION_KEY = 'db';

	/** @var array */
	private $config = [];

	/** @var array */
	private $connections = [];

	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->parseConfig($config);
	}

	/**
	 * @param string $db
	 * @return resource|null
	 */
	public function getConnection($db = self::DEFAULT_DB_CONNECTION_KEY)
	{
		if (!array_key_exists($db, $this->connections)) {
			$dbList = $this->config;

			if (!isset($dbList[$db])) {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "There are no settings to connect to DB '$db'",
				]);
				return null;
			}

			$dbConfig = $dbList[$db];
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
	 * @param string $db
	 */
	public function closeConnection($db = self::DEFAULT_DB_CONNECTION_KEY)
	{
		if (isset($this->connections[$db])) {
			$this->connections[$db]->close();
			unset($this->connections[$db]);
		}
	}

	/**
	 * Config can have 'db' key with value which is single (default) connection settings
	 * Config can have 'dbList' key with value which is array with list of connection settings
	 *
	 * @param array $config
	 */
	private function parseConfig($config)
	{
		$dbList = [];
		if (isset($config['db'])) {
			$dbList['db'] = $config['db'];
		}
		if (isset($config['dbList'])) {
			$dbList += $config['dbList'];
		}

		$this->config = $dbList;
	}
}
