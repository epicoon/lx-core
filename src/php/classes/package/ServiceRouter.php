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
	public function route($route) {
		$map = $this->getMap();
		/*
		Пример карты:
		[
			'test/view' => [
				'controller' => 'psrTest\controller\MainController',
				'method' => 'get',
				'access' => 'full',
				'on-mode' => 'dev',
				'on-service-mode' => 'someMode',
			],
			'test/a' => 'psrTest\controller\SomeController',  // Контроллер должен иметь метод ::run() - именно он будет запущен
			'test/a' => 'psrTest\controller\SomeController::someAction',
			'test/b' => ['action' => 'psrTest\action\SomeAction'],  // Экшен должен иметь метод ::run() - именно он будет запущен

			'route/c' => ['module' => 'someModule'],  // Сразу замыкает на модуль - он будет отрендерен
		]
		*/

		$className = null;
		foreach ($map as $routeKey => $data) {
			// На уровне роутера сервиса должно быть точное соответствие роута
			if ($route != $routeKey) {
				continue;
			}

			// Строка может означать только имя класса контроллера
			if (is_string($data)) {
				return $this->responseByController($data);
			}

			// Массив отработает по-разному в зависимости от наличия одного из ключей 'controller', 'action', 'module'
			if (is_array($data)) {
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

				//todo - проверки метода, доступа

				if (isset($data['controller'])) {
					return $this->responseByController($data['controller']);
				}

				if (isset($data['action'])) {
					return $this->responseByAction($data['action']);
				}

				if (isset($data['module'])) {
					return $this->responseByModule($data['module']);
				}
			}
		}

		return false;
	}

	/**
	 *
	 * */
	public function responseByController($nameWithAction) {
		$className = '';
		$actionMethod = 'run';
		$arr = explode('::', $nameWithAction);
		$className = $arr[0];
		if (isset($arr[1])) $actionMethod = $arr[1];

		if (!ClassHelper::exists($className)) {
			return false;
		}

		$controller = new $className($this->service);
		if (!method_exists($controller, $actionMethod)) {
			return false;
		}

		return $controller->$actionMethod(\lx::$dialog->params());
	}

	/**
	 *
	 * */
	public function responseByAction($className) {
		if (!ClassHelper::exists($className)) {
			return false;
		}

		$action = new $className($this->service);
		if (!method_exists($action, 'run')) {
			return false;
		}

		return $action->run(\lx::$dialog->params());		
	}

	/**
	 *
	 * */
	public function responseByModule($moduleName) {
		$module = $this->service->getModule($moduleName);
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
}
