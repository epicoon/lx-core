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
	public function getList() {
		$dir = $this->service->conductor->getMigrationDirectory();
		$names = $dir->getContent(['findType' => Directory::FIND_NAME, 'ext' => false]);
		$list = $names->getData();
		usort($list, function($a, $b) {
			$aTime = explode('__', $a)[1];
			$bTime = explode('__', $b)[1];
			if ($aTime == $bTime) return 0;
			return ($aTime < $bTime) ? -1 : 1;
		});

		return $list;
	}

	/**
	 *
	 * */
	public function getUnappliedList() {
		$list = $this->getList();
		if (empty($list)) return [];

		MigrationMap::getInstance()->open();

		$result = [];
		foreach ($list as $migrationName) {
			if ( ! MigrationMap::getInstance()->check($this->service->name, $migrationName)) {
				$result[] = $migrationName;
			}
		}

		MigrationMap::getInstance()->close();

		return $result;
	}

	/**
	 *
	 * */
	public function getDetailedList() {
		$list = $this->getList();
		if (empty($list)) return [];

		MigrationMap::getInstance()->open();

		$result = [];
		foreach ($list as $migrationName) {
			$arr = explode('__', $migrationName);

			//TODO сработает некорректно, если имя модели имеет нижнее подчеркивание. Надо открывать файл и читать имя модели оттуда
			$migrationData = explode('_', $arr[2], 2);

			$result[] = [
				'name' => $migrationName,
				'createdAt' => date('Y-m-d H:i:s', explode('_', $arr[1])[0]),
				'model' => $migrationData[0],
				'type' => $migrationData[1],
				'applied' => MigrationMap::getInstance()->check($this->service->name, $migrationName),
			];
		}

		MigrationMap::getInstance()->close();

		return $result;
	}
}
