<?php

namespace lx;

class Router {
	protected $map;

	/**
	 *
	 * */
	public function setMap($map) {
		$this->map = $map;
	}

	/**
	 *
	 * */
	public function getMap() {
		return $this->map;
	}

	/**
	 * Роутер уровня приложения определяет на какой сервис перенаправить запрос и передает управление запросом на роутер сервиса
	 * */
	public function route() {
		$map = $this->getMap();
		$route = \lx::$dialog->route();

		$service = null;
		foreach ($map as $routeKey => $serviceData) {
			$serviceName = is_string($serviceData)
				? $serviceData
				: (isset($serviceData['service']) ? $serviceData['service'] : null);
			if ($serviceName === null) {
				return false;
			}

			// Проверка мода
			if (is_array($serviceData)) {
				if (isset($serviceData['on-mode'])) {
					if (!\lx::isMode($serviceData['on-mode'])) {
						return false;
					}
				}
			}

			if ($routeKey{0} == '~') {
				$reg = preg_replace('/^~/', '/', $routeKey) . '/';
				if (preg_match($reg, $route)) {
					$service = Service::create($serviceName);
				}
			} else {
				if ($route == $routeKey) {
					$service = Service::create($serviceName);
				}
			}
		}

		if ($service === null) {
			return false;
		}

		$serviceRouter = $service->router();
		return $serviceRouter->route();
	}
}
