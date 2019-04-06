<?php

namespace lx;

class MigrationMap {
	private static $_instance = null;
	private $map = null;
	private $counter = 0;

	private function __construct() {}
	private function __clone() {}

	/**
	 *
	 * */
	public static function getInstance() {
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 *
	 * */
	public function open() {
		$this->counter++;
		if ($this->map !== null) return;

		$path = $this->fileName();
		if (file_exists($path)) {
			$data = file_get_contents($path);
			$data = json_decode($data, true);
			$this->map = $data;
		} else {
			$this->map = [];
		}
	}

	/**
	 *
	 * */
	public function close() {
		$this->counter--;
		if ($this->counter == 0) {
			$path = $this->fileName();
			$data = json_encode($this->map);
			file_put_contents($path, $data);
			$this->map = null;
		}
	}

	/**
	 *
	 * */
	public function up($service, $migrationName) {
		$this->open();
		if (!array_key_exists($service, $this->map)) {
			$this->map[$service] = [];
		}
		$this->map[$service][] = $migrationName;
		$this->close();
	}

	/**
	 *
	 * */
	public function down($service, $migrationName) {
		$this->open();

		if (!array_key_exists($service, $this->map)) {
			$this->close();
			return;
		}

		$index = array_search($migrationName, $this->map);
		if ($index === false) {
			$this->close();
			return;
		}

		array_splice($this->map, $index, 1);
		$this->close();
	}

	/**
	 *
	 * */
	public function check($service, $migrationName) {
		if ($this->map === null) {
			throw new \Exception("MigrationMap was not opened", 400);
		}

		if (!array_key_exists($service, $this->map)) {
			return false;
		}

		return array_search($migrationName, $this->map[$service]) !== false;
	}

	/**
	 *
	 * */
	private function fileName() {
		return \lx::$conductor->getSystemPath('system') . '/migrations.json';
	}
}
