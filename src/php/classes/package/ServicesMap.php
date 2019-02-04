<?php

namespace lx;

class ServicesMap {
	private $map = [];

	/* Синглтон */
	private static $instance = null;
	private function __construct() {}
	private function __clone() {}
	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *
	 * */
	public static function has($serviceName) {
		$self = self::getInstance();
		return array_key_exists($serviceName, $self->map);
	}

	/**
	 *
	 * */
	public static function get($serviceName) {
		$self = self::getInstance();
		if (!array_key_exists($serviceName, $self->map)) {
			Service::create($serviceName);
		}
		return $self->map[$serviceName];
	}

	/**
	 *
	 * */
	public static function newService($serviceName, $service) {
		$self = self::getInstance();
		$self->map[$serviceName] = $service;
	}
}
