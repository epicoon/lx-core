<?php

namespace lx;

/**
 * Класс для создания роутеров в сервисах
 * */
class ServiceRouter {
	protected $service;

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
	 * Должен возвращать данные от объекте, куда перенаправляется запрос
	 * */
	public function route() {
		$map = $this->getMap();
		/*
		Пример карты:
		[
			'test/view' => [
				'controller' => 'psrTest\controller\MainController',
				'action' => 'eee',
				'method' => 'get',
				'access' => 'full',
			],
			'test/a' => 'psrTest\controller\SomeController',  // Контроллер должен иметь метод ::run() - именно он будет запущен
			'test/b' => 'psrTest\action\SomeAction',          // Экшен должен иметь метод ::run() - именно он будет запущен
			'test/a' => 'psrTest\controller\SomeController::someAction',

			'route/c' => '{module:someModule}',  // Сразу замыкает на модуль - он будет отрендерен
		]
		*/

		$className = null;
		$route = \lx::$dialog->route();
		foreach ($map as $routeKey => $data) {
			if ($route != $routeKey) {
				continue;
			}

			$className = '';
			$actionMethod = 'run';
			if (is_string($data)) {
				if ($data{0} == '{' || $data{0} == '[') {
					return $this->routeByObject($data);
				}

				$arr = explode('::', $data);
				$className = $arr[0];
				if (isset($arr[1])) $actionMethod = $arr[1];
			} else {
				//todo проверка метода
				if (isset($data['method'])) {

				}

				if (isset($data['controller'])) {
					$className = $data['controller'];
					if (isset($data['action'])) {
						$actionMethod = $data['action'];
					}
				} elseif (isset($data['action'])) {
					$className = $data['action'];
				}
			}
		}

		if ($className === null || !ClassHelper::exists($className)) {
			return false;
		}

		$instance = new $className($this->service);
		$response = $instance->$actionMethod(\lx::$dialog->params());

		return $response;
	}

	/**
	 * Вернет отрендеренный модуль
	 * {module:name}
	 * */
	private function routeByObject($info) {
		$info = trim($info, '{}[]');
		$data = explode(':', $info);
		$key = trim($data[0], ' ');
		$value = trim($data[1], ' ');

		if ($key == 'module') {
			$module = $this->service->getModule($value);
			if (!$module) return false;

			/*
			//todo AJAX остался в app.php - в общей картине будет яснее. Нужна ли поддержка "ручного" управления AJAX-запросами на стороне сервера?
			было бы неплохо, но как это сделать
			*/
			if (!\lx::$dialog->isAjax()) {
				return ServiceResponse::renderModule($module);
			// } else {
			// 	return ServiceResponse::ajaxForModule($module);
			}
		}

		return false;
	}
}
