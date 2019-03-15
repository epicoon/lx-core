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
		$seviceRoute = $route;
		foreach ($map as $routeKey => $serviceData) {
			// Проверка мода
			if (is_array($serviceData)) {
				if (isset($serviceData['on-mode'])) {
					if (!\lx::isMode($serviceData['on-mode'])) {
						continue;
					}
				}
			}

			if (!$this->validateRouteKey($routeKey, $route)) {
				continue;
			}

			if ($routeKey{0} == '!') {
				$seviceRoute = (explode(preg_replace('/^!/', '', $routeKey), $route))[1];
			}

			$serviceName = null;
			if (is_string($serviceData)) {
				return $this->responceByService($serviceData, $seviceRoute);
			}

			if (!is_array($serviceData)) {
				return false;
			}

			if (isset($serviceData['service'])) {
				return $this->responceByService($serviceData['service'], $seviceRoute);
			}

			if (isset($serviceData['service-route'])) {
				return $this->responceByServiceRoute($serviceData['service-route']);
			}

			if (isset($serviceData['service-controller'])) {
				return $this->responceByServiceController($serviceData['service-controller']);
			}

			if (isset($serviceData['service-action'])) {
				return $this->responceByServiceAction($serviceData['service-action']);
			}

			if (isset($serviceData['service-module'])) {
				return $this->responceByServiceModule($serviceData['service-module']);
			}
		}

		return false;
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function responceByService($serviceName, $route) {
		$service = Service::create($serviceName);
		if ($service === null) {
			return false;
		}

		$serviceRouter = $service->router();
		return $serviceRouter->route($route);
	}

	/**
	 * serviceName:route/in/service
	 * */
	private function responceByServiceRoute($serviceRoute) {
		preg_match_all('/^([^:]*?):(.*?)$/', $serviceRoute, $matches);
		$serviceName = $matches[1][0];
		if (!Service::exists($serviceName)) {
			return false;
		}

		$service = Service::create($serviceName);
		$route = $matches[2][0];
		$serviceRouter = $service->router();
		return $serviceRouter->route($route);
	}

	/**
	 * serviceName:ControllerName::actionName
	 * */
	private function responceByServiceController($serviceController) {
		preg_match_all('/^([^:]*?):(.*?)$/', $serviceController, $matches);
		$serviceName = $matches[1][0];
		if (!Service::exists($serviceName)) {
			return false;
		}

		$service = Service::create($serviceName);
		$controller = $matches[2][0];
		$serviceRouter = $service->router();
		return $serviceRouter->responseByController($controller);
	}

	/**
	 * serviceName:ActionName
	 * */
	private function responceByServiceAction($serviceAction) {
		preg_match_all('/^([^:]*?):(.*?)$/', $serviceAction, $matches);
		$serviceName = $matches[1][0];
		if (!Service::exists($serviceName)) {
			return false;
		}

		$service = Service::create($serviceName);
		$action = $matches[2][0];
		$serviceRouter = $service->router();
		return $serviceRouter->responseByAction($action);
	}

	/**
	 * serviceName:moduleName
	 * */
	private function responceByServiceModule($serviceModule) {
		preg_match_all('/^([^:]*?):(.*?)$/', $serviceModule, $matches);
		$serviceName = $matches[1][0];
		if (!Service::exists($serviceName)) {
			return false;
		}

		$service = Service::create($serviceName);
		$module = $matches[2][0];
		$serviceRouter = $service->router();
		return $serviceRouter->responseByModule($module);
	}

	/**
	 *
	 * */
	private function validateRouteKey($routeKey, $route) {
		if ($routeKey{0} == '~') {
			$reg = preg_replace('/^~/', '/', $routeKey) . '/';
			return preg_match($reg, $route);
		}

		if ($routeKey{0} == '!') {
			$reg = preg_replace('/^!/', '/^', $routeKey) . '/';
			return preg_match($reg, $route);
		}

		return $routeKey == $route;
	}
}
