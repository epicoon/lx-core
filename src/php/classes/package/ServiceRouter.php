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
			if (!$this->validateConditions($data)) {
				return false;
			}

			if (is_string($data)) {
				$data = ['controller' => $data];
			}

			if (is_array($data)) {
				$info = $data['controller'] ?? $data['action'] ?? null;
				if ($info) {
					$arr = $this->getControllerData($info);
					if (!$arr) {
						return false;
					}
					$source['class'] = $arr[0];
					$source['method'] = $arr[1];
				} elseif (isset($data['plugin'])) {
					if (!$this->service->pluginExists($data['plugin'])) {
						return false;
					}
					$source['plugin'] = $data['plugin'];
				}
			}
		}

		return new SourceContext($source);
	}

	/**
	 *
	 * */
	protected function getControllerData($nameWithAction) {
		$arr = explode('::', $nameWithAction);
		$className = $arr[0];
		$actionMethod = $arr[1] ?? 'run';

		if (ClassHelper::exists($className) && method_exists($className, $actionMethod)) {
			return [$className, $actionMethod];
		}

		return false;
	}

	/**
	 * Проверка общих условий доступа
	 * */
	private function validateConditions($data) {
		if ( ! is_array($data)) {
			return true;
		}

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
