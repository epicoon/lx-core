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
		
	}

	/**
	 *
	 * */
	public function renderModule($module) {
		if (is_string($module)) {
			$module = $this->service->getModule($module);
		}

		return ServiceResponse::renderModule($module);
	}
}
