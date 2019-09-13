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
	public function route($serviceRouteData) {
		return $this->determineRouteData($serviceRouteData);
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
	 *		'route/a' => [
	 *			'controller' => 'psrTest\controller\MainController',
	 *			'method' => 'get',
	 *			'on-mode' => 'dev',
	 *			'on-service-mode' => 'someMode',
	 *		],
	 * 
	 *		'route/b' => 'psrTest\controller\SomeController',  // Контроллер должен иметь метод ::run() - именно он будет запущен
	 * 
	 *		'route/c' => 'psrTest\controller\SomeController::someAction',
	 *		'route/d' => ['action' => 'psrTest\action\SomeAction'],  // Экшен должен иметь метод ::run() - именно он будет запущен
	 *
	 *		'route/e' => ['plugin' => 'somePlugin'],  // Сразу замыкает на плагин - он будет отрендерен
	 *	]
	 * */
	protected function determineRouteData($routeData) {
		$source = $routeData;
		if (isset($source['route'])) {
			$route = $source['route'];
			unset($source['route']);

			$map = $this->getMap();
			if (!array_key_exists($route, $map)) {
				return false;
			}

			$data = $map[$route];
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
				} elseif (isset($data['plugin'])) {
					if (!$this->service->pluginExists($data['plugin'])) {
						return false;
					}
					$source['plugin'] = $data['plugin'];
				} elseif (isset($data['static'])) {
					$arr = explode('::', $data['static']);
					$source['isStatic'] = true;
					$source['class'] = $arr[0];
					$source['method'] = $arr[1];
				}
			}
		}

		return new ResponseSource($this->service->app, $source);
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
			if (!$this->service->app->isMode($data['on-mode'])) {
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
