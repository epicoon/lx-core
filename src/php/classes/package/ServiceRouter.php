<?php

namespace lx;

/**
 * Класс для создания роутеров в сервисах
 * */
class ServiceRouter {
	protected $service;

	/**
	 *
	 * */
	public static function route($serviceRouteData) {
		$serviceName = $serviceRouteData['service'];
		if (!Service::exists($serviceName)) {
			return false;
		}

		$service = Service::create($serviceName);
		$router = $service->router();
		$routeData = $router->determineRouteData($serviceRouteData);


		return $routeData;
	}

	/**
	 *
	 * */
	public function __construct($service) {
		$this->service = $service;
	}

	/**
	 *
	 * */
	public function getMap() {
		$map = $this->service->getConfig('service.router');
		if ($map && isset($map['type']) && $map['type'] == 'map') {
			if (isset($map['routes'])) {
				return $map['routes'];
			}
		}
		return [];
	}

	/**
	 *	Пример карты:
	 *	[
	 *		'test/view' => [
	 *			'controller' => 'psrTest\controller\MainController',
	 *			'method' => 'get',
	 *			'on-mode' => 'dev',
	 *			'on-service-mode' => 'someMode',
	 *		],
	 *		'test/a' => 'psrTest\controller\SomeController',  // Контроллер должен иметь метод ::run() - именно он будет запущен
	 *		'test/a' => 'psrTest\controller\SomeController::someAction',
	 *		'test/b' => ['action' => 'psrTest\action\SomeAction'],  // Экшен должен иметь метод ::run() - именно он будет запущен
	 *
	 *		'route/c' => ['module' => 'someModule'],  // Сразу замыкает на модуль - он будет отрендерен
	 *	]
	 * */
	protected function determineRouteData($routeData) {
		$source = $routeData;
		if (isset($source['route'])) {
			$route = $source['route'];
			unset($source['route']);

			$map = $this->getMap();
			$match = false;
			foreach ($map as $routeKey => $data) {
				// На уровне роутера сервиса должно быть точное соответствие роута
				if ($route != $routeKey) {
					continue;
				}

				if (is_string($data)) {
					$source['controller'] = $data;
				} elseif (is_array($data)) {
					if (!$this->validateConditions($data)) {
						return false;
					}

					if (isset($data['controller'])) {
						$arr = $this->getControllerData($data['controller']);
						if (!$arr) {
							return false;
						}
						$source['action'] = true;
						$source['class'] = $arr[0];
						$source['method'] = $arr[1];
					} elseif (isset($data['action'])) {
						$arr = $this->getActionData($data['action']);
						if (!$arr) {
							return false;
						}
						$source['action'] = true;
						$source['class'] = $arr[0];
						$source['method'] = $arr[1];
					} elseif (isset($data['module'])) {
						if (!$this->service->moduleExists($data['module'])) {
							return false;
						}
						$source['module'] = $data['module'];
					} elseif (isset($data['static'])) {
						$arr = explode('::', $data['static']);
						$source['isStatic'] = true;
						$source['class'] = $arr[0];
						$source['method'] = $arr[1];
					}
				}

				$match = true;
				break;
			}

			if (!$match) {
				return false;
			}
		}

		return new ResponseSource($source);
	}

	/**
	 *
	 * */
	protected function getControllerData($nameWithAction) {
		$arr = explode('::', $nameWithAction);
		$className = $arr[0];
		$actionMethod = isset($arr[1]) ? $arr[1] : 'run';

		if (!ClassHelper::exists($className) || !method_exists($controller, $actionMethod)) {
			return false;
		}

		return [$className, $actionMethod];
	}

	/**
	 *
	 * */
	protected function getActionData($className) {
		if (!ClassHelper::exists($className) || !method_exists($className, 'run')) {
			return false;
		}

		return [$className, 'run'];
	}

	/**
	 * Проверка общих условий доступа
	 * */
	private function validateConditions($data) {
		// Проверка мода приложения
		if (isset($data['on-mode'])) {
			if (!\lx::isMode($data['on-mode'])) {
				return false;
			}
		}

		// Проверка мода сервиса
		if (isset($data['on-service-mode'])) {
			if (!$this->service->isMode($data['on-service-mode'])) {
				return false;
			}
		}

		return true;
	}
}
