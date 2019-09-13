<?php

namespace lx;

class ServiceController {
	protected $service;

	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * */
	public function run($params) {
		return false;
	}

	/**
	 *
	 * */
	public function renderPlugin($plugin) {
		if (is_string($plugin)) {
			$plugin = $this->service->getPlugin($plugin);
		}

		return ServiceResponse::renderPlugin($plugin);
	}
}
