<?php

namespace lx;

class ServicesMap extends ApplicationTool {
	private $map = [];

	public function exists($serviceName) {
		try {
			Service::create($this->app, $serviceName);
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function has($serviceName) {
		return array_key_exists($serviceName, $this->map);
	}

	public function get($serviceName) {
		if (!$this->has($serviceName)) {
			Service::create($this->app, $serviceName);
		}
		return $this->map[$serviceName];
	}

	public function register($serviceName, $service) {
		$this->map[$serviceName] = $service;
	}
}
