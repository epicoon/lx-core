<?php

namespace lx;

class Router implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	protected array $routes = [];

	public function route(): ?ResourceContext
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
				return null;
			}

			$service = $this->app->getService($serviceRouteData['service']);
			if (!$service) {
				return null;
			}

			$serviceRouter = $service->router;
			if (!$serviceRouter) {
				return null;
			}

			return $serviceRouter->route($serviceRouteData);
		}

		return null;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function determineServiceRouteData(array $routeData, string $route): array
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
	 */
	private function determineRouteData($data): array
	{
		if (is_string($data)) {
			return ['service' => $data];
		}

		return $data;
	}

	private function determineServiceRoute(string $routeKey, string $route): ?string
	{
		if (!$this->validateRouteKey($routeKey, $route)) {
			return null;
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

	private function validateRouteKey(string $routeKey, string $route): bool
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

	private function validateRouteData(array $routeData): bool
	{
		if (isset($routeData['on-mode']) && !$this->app->isMode($routeData['on-mode'])) {
			return false;
		}

		return true;
	}
}
