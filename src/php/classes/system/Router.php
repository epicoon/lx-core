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
	 * Роутер уровня приложения определяет на какой сервис перенаправить запрос
	 * передает управление запросом на роутер сервиса
	 * возвращает информацию о запрашиваемом ресурсе
	 * */
	public function route() {
		$map = $this->getMap();
		foreach ($map as $routeKey => $data) {
			$routeData = $this->determineRouteData($data);
			if (!$this->validateRouteData($routeData)) {
				continue;
			}

			$route = $this->determineRoute($routeKey, \lx::$dialog->route());
			if (!$route) {
				continue;
			}

			$serviceRouteData = $this->determineServiceRouteData($routeData, $route);
			return ServiceRouter::route($serviceRouteData);
		}

		return false;
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function determineServiceRouteData($routeData, $route) {
		if (isset($routeData['service'])) {
			return [
				'service' => $routeData['service'],
				'route' => $route,
			];
		}

		if (isset($routeData['service-route'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['service-route'], $matches);
			return [
				'service' => $matches[1][0],
				'route' => $matches[2][0],
			];
		}

		if (isset($routeData['service-controller'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['service-controller'], $matches);
			return [
				'service' => $matches[1][0],
				'controller' => $matches[2][0],
			];
		}

		if (isset($routeData['service-action'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['service-action'], $matches);
			return [
				'service' => $matches[1][0],
				'action' => $matches[2][0],
			];
		}

		if (isset($routeData['service-module'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['service-module'], $matches);
			return [
				'service' => $matches[1][0],
				'module' => $matches[2][0],
			];
		}
	}

	/**
	 *
	 * */
	private function determineRouteData($data) {
		if (is_string($data)) {
			return ['service' => $data];
		}

		return $data;
	}

	/**
	 *
	 * */
	private function determineRoute($routeKey, $route) {
		if (!$this->validateRouteKey($routeKey, $route)) {
			return false;
		}

		$serviceRoute = $route;
		if ($routeKey{0} == '!') {
			$serviceRoute = preg_replace('/^!/', '', $routeKey);
			if ($serviceRoute != '') {
				$serviceRoute = (explode($serviceRoute, $route))[1];
				$serviceRoute = preg_replace('/^\//', '', $serviceRoute);
			}
			if ($serviceRoute == '') {
				$serviceRoute = '/';
			}
		}

		return $serviceRoute;
	}

	/**
	 *
	 * */
	private function validateRouteKey($routeKey, $route) {
		if ($routeKey{0} == '~') {
			$reg = preg_replace('/^~/', '/', str_replace('/', '\/', $routeKey)) . '/';
			return preg_match($reg, $route);
		}

		if ($routeKey{0} == '!') {
			$reg = preg_replace('/^!/', '/^', str_replace('/', '\/', $routeKey)) . '/';
			return preg_match($reg, $route);
		}

		return $routeKey == $route;
	}

	/**
	 * Проверка общих условий доступа
	 * */
	private function validateRouteData($routeData) {
		// Проверка мода
		if (isset($routeData['on-mode']) && !\lx::isMode($routeData['on-mode'])) {
			return false;
		}

		return true;
	}
}
