<?php

namespace lx;

class ServicesMap
{
	private array $map = [];

	public function exists(string $serviceName): bool
	{
		return array_key_exists($serviceName, Autoloader::getInstance()->map->services);
	}

	public function has(string $serviceName): bool
	{
		return array_key_exists($serviceName, $this->map);
	}

	public function get(string $serviceName): ?Service
	{
		if (!$this->has($serviceName)) {
			Service::create($serviceName);
		}

		return $this->map[$serviceName] ?? null;
	}

	public function register(string $serviceName, Service $service): void
	{
		$this->map[$serviceName] = $service;
	}
}
