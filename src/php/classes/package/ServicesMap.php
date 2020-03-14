<?php

namespace lx;

/**
 * Class ServicesMap
 * @package lx
 */
class ServicesMap
{
	/** @var array */
	private $map = [];

	/**
	 * @param string $serviceName
	 * @return bool
	 */
	public function exists($serviceName)
	{
		return array_key_exists($serviceName, Autoloader::getInstance()->map->packages);
	}

	/**
	 * @param string $serviceName
	 * @return bool
	 */
	public function has($serviceName)
	{
		return array_key_exists($serviceName, $this->map);
	}

	/**
	 * @param string $serviceName
	 * @return Service
	 */
	public function get($serviceName)
	{
		if (!$this->has($serviceName)) {
			Service::create($serviceName);
		}

		return $this->map[$serviceName] ?? null;
	}

	/**
	 * @param string $serviceName
	 * @param Service $service
	 */
	public function register($serviceName, $service)
	{
		$this->map[$serviceName] = $service;
	}
}
