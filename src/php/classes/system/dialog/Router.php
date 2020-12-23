<?php

namespace lx;

/**
 * Class Router
 * @package lx
 */
class Router implements FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var array */
	protected $routes = [];

	/**
	 * @return ResourceContext|false
	 */
	public function route()
	{
		foreach ($this->routes as $routeKey => $data) {
			$routeData = $this->determineRouteData($data);
			if (!$this->validateRouteData($routeData)) {
				continue;
			}

			$serviceRoute = $this->determineServiceRoute($routeKey, $this->app->dialog->getRoute());
			if (!$serviceRoute) {
				continue;
			}

			$serviceRouteData = $this->determineServiceRouteData($routeData, $serviceRoute);
			if (!isset($serviceRouteData['service'])) {
				return false;
			}

			$service = $this->app->getService($serviceRouteData['service']);
			if (!$service) {
				return false;
			}

			$serviceRouter = $service->router;
			if (!$serviceRouter) {
				return false;
			}

			return $serviceRouter->route($serviceRouteData);
		}

		return false;
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 * @param array $routeData
	 * @param string $route
	 * @return array
	 */
	private function determineServiceRouteData($routeData, $route)
	{
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

		if (isset($routeData['service-plugin'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['service-plugin'], $matches);
			$result = [
				'service' => $matches[1][0],
				'plugin' => $matches[2][0],
			];
			if (isset($routeData['attributes'])) {
				$result['attributes'] = $routeData['attributes'];
			}
			if (isset($routeData['dependencies'])) {
				$result['dependencies'] = $routeData['dependencies'];
			}

			return $result;
		}
	}

	/**
	 * @param array|string $data
	 * @return array
	 */
	private function determineRouteData($data)
	{
		if (is_string($data)) {
			return ['service' => $data];
		}

		return $data;
	}

	/**
	 * @param string $routeKey
	 * @param string $route
	 * @return string|false
	 */
	private function determineServiceRoute($routeKey, $route)
	{
		if (!$this->validateRouteKey($routeKey, $route)) {
			return false;
		}

		$serviceRoute = $route;
		if ($routeKey[0] == '!') {
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
	 * @param string $routeKey
	 * @param string $route
	 * @return bool
	 */
	private function validateRouteKey($routeKey, $route)
	{
		if ($routeKey[0] == '~') {
			$reg = preg_replace('/^~/', '/', str_replace('/', '\/', $routeKey)) . '/';
			return preg_match($reg, $route);
		}

		if ($routeKey[0] == '!') {
			$reg = preg_replace('/^!/', '/^', str_replace('/', '\/', $routeKey)) . '/';
			return preg_match($reg, $route);
		}

		return $routeKey == $route;
	}

	/**
	 * @param array $routeData
	 * @return bool
	 */
	private function validateRouteData($routeData)
	{
		if (isset($routeData['on-mode']) && !$this->app->isMode($routeData['on-mode'])) {
			return false;
		}

		return true;
	}
}
