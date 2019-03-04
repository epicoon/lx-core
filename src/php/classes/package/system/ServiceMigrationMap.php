<?php

namespace lx;

class ServiceMigrationMap {
	private $service;

	/**
	 *
	 * */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * */
	public function getMapFile() {
		$path = $this->service->conductor->getMigrationDirectory()->getPath() . '/map.json';
		if (!file_exists($path)) {
			return false;
		}

		return new File($path);
	}

	/**
	 *
	 * */
	public function getMap() {
		$f = $this->getMapFile();
		if (!$f) return [];

		$data = json_decode($f->get(), true);
		$list = $data['list'];
		usort($list, function($a, $b) {
			if ($a['time']===$b['time']) return 0;
			return ($a['time'] < $b['time']) ? -1 : 1;
		});

		return $list;
	}

	/**
	 *
	 * */
	public function getUnappliedList() {
		$result = [];
		$list = $this->getMap();
		if (empty($list)) return [];

		MigrationMap::getInstance()->open();
		foreach ($list as $migrationRow) {
			if (!MigrationMap::getInstance()->check($this->service->name, $migrationRow['name'])) {
				$result[] = $migrationRow['name'];
			}
		}
		MigrationMap::getInstance()->close();
		return $result;
	}
}
