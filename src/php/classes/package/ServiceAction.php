<?php

namespace lx;

abstract class ServiceAction {
	protected $service;

	public function __construct($service) {
		$this->service = $service;
	}

	abstract public function run();
}
