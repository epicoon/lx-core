<?php

namespace lx;

/**
 * Class DbConnectionList
 * @package lx
 */
class DbConnectionList
{
	/** @var array */
	private $list = [];

	/**
	 * @param array $settings
	 * @return array|false - [string{type}, resource{connection}]
	 */
	public function add($settings)
	{
		$key = $settings['hostname'] . '_' . $settings['username'] . '_' . $settings['dbName'];
		if (array_key_exists($key, $this->list)) {
			$this->list[$key]['count']++;
		} else {
			$connect = $this->tryConnect($settings);

			if (!$connect) {
				return false;
			}

			$this->list[$key] = [
				'type' => $connect['type'],
				'connection' => $connect['connection'],
				'count' => 1,
			];
		}

		return [$this->list[$key]['type'], $this->list[$key]['connection']];
	}

	/**
	 * @param array $settings
	 */
	public function drop($settings)
	{
		$key = $settings['hostname'] . '_' . $settings['username'] . '_' . $settings['dbName'];
		if (array_key_exists($key, $this->list)) {
			$this->list[$key]['count']--;

			if ($this->list[$key]['count'] == 0) {
				$this->closeConnection($key);
				unset($this->list[$key]);
			}
		}
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $key
	 */
	private function closeConnection($key)
	{
		if (!array_key_exists($key, $this->list) || $this->list[$key]['count'] > 0) {
			return;
		}

		switch ($this->list[$key]['type']) {
			case DB::POSTGRESQL:
				pg_close($this->list[$key]['connection']);
				break;
			case DB::MYSQL:
				mysqli_close($this->list[$key]['connection']);
				break;
		}
	}

	/**
	 * @param array $settings
	 * @return array|false - ['type' => string, 'connection' => resource]
	 */
	private function tryConnect($settings)
	{
		$arr = isset($settings['db'])
			? [$settings['db']]
			: [DB::POSTGRESQL, DB::MYSQL];
		foreach ($arr as $value) {
			$method = 'try_' . $value;

			$connection = $this->$method($settings);
			if ($connection) {
				return [
					'connection' => $connection,
					'type' => $value,
				];
			}
		}

		return false;
	}

	/**
	 * @param array $settings
	 * @return \mysqli|false
	 */
	private function try_mysql($settings)
	{
		$connection = false;
		try {
			$connection = mysqli_connect($settings['hostname'], $settings['username'], $settings['password']);
		} catch (\Exception $e) {
			return false;
		}

		if (!$connection) {
			return false;
		}

		mysqli_select_db($connection, $settings['dbName']);
		mysqli_set_charset($connection, 'utf8');
		return $connection;
	}

	/**
	 * @param array $settings
	 * @return resource|false
	 */
	private function try_pg($settings)
	{
		if (!function_exists('\pg_connect')) {
			return false;
		}

		$connection = false;
		try {
			$str = "host={$settings['hostname']}"
				. " dbname={$settings['dbName']}"
				. " user={$settings['username']}"
				. " password={$settings['password']}";
			$connection = \pg_connect($str);
		} catch (\Exception $e) {
			return false;
		}

		if (!$connection) {
			return false;
		}

		return $connection;
	}
}
