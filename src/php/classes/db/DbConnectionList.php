<?php

namespace lx;

class DbConnectionList {
	private $list = [];

	/**
	 *
	 * */
	public function add($settings) {
		$key = $settings['hostname'] . '_' . $settings['username'] . '_' . $settings['dbName'];
		if (array_key_exists($key, $this->list)) {
			$this->list[$key]['count']++;

			//todo если был 0 и было отключено - надо снова установить соединение. Но пока не отключаю
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
	 *
	 * */
	public function drop($settings) {
		$key = $settings['hostname'] . '_' . $settings['username'] . '_' . $settings['dbName'];
		if (array_key_exists($key, $this->list)) {
			$this->list[$key]['count']--;

			//todo при достижении 0 надо ли отключать
		}
	}

	/**
	 *
	 * */
	private function closeConnection($key) {
		if (!array_key_exists($key, $this->list) || $this->list[$key]['count'] > 0) {
			return;
		}

		switch ($this->list[$key]['type']) {
			case 'pg':
				pg_close($this->list[$key]['connection']);
				break;
			case 'mysql':
				mysqli_close($this->list[$key]['connection']);
				break;			
		}
	}

	/**
	 *
	 * */
	private function tryConnect($settings) {
		$arr = isset($settings['db'])
			? [$settings['db']]
			: ['pg', 'mysql'];
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
	 *
	 * */
	private function try_mysql($settings) {
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
	 *
	 * */
	private function try_pg($settings) {
		if (!function_exists('\pg_connect')) return false;

		$connection = false;
		try {
			$connection = \pg_connect("host={$settings['hostname']} dbname={$settings['dbName']} user={$settings['username']} password={$settings['password']}");
		} catch (\Exception $e) {
			return false;
		}

		if (!$connection) {
			return false;
		}

		return $connection;
	}
}
