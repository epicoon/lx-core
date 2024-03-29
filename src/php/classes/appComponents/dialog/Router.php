<?php

namespace lx;

use lx;

class Router implements RouterInterface, FusionComponentInterface
{
	use FusionComponentTrait;

	protected array $routes = [];
    protected string $ignorePrefix = '';
    protected string $assetPrefix = '';

	public function route(string $route): ?ResourceContextInterface
	{
        if ($route != '/' && $this->ignorePrefix !== '') {
            $route = preg_replace('/^' . $this->ignorePrefix . '/', '', $route);
            $route = preg_replace('/^\//', '', $route);
        }

		foreach ($this->routes as $routeKey => $data) {
			$routeData = $this->determineRouteData($data);
			if (!$this->validateRouteData($routeData)) {
				continue;
			}

			$serviceRoute = $this->determineServiceRoute($routeKey, $route);
			if (!$serviceRoute) {
				continue;
			}

			$serviceRouteData = $this->determineServiceRouteData($routeData, $serviceRoute);
			if (!isset($serviceRouteData['service'])) {
				return null;
			}

			$service = lx::$app->getService($serviceRouteData['service']);
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

    public function getAssetPrefix(): string
    {
        if ($this->assetPrefix === '') {
            return '';
        }

        $prefix = $this->assetPrefix;
        if ($prefix[0] != '/') {
            $prefix = '/' . $prefix;
        }
        return $prefix;
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

		if (isset($routeData['controller'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['controller'], $matches);
			return [
				'service' => $matches[1][0],
				'controller' => $matches[2][0],
			];
		}

		if (isset($routeData['action'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['action'], $matches);
			return [
				'service' => $matches[1][0],
				'action' => $matches[2][0],
			];
		}

		if (isset($routeData['plugin'])) {
			preg_match_all('/^([^:]*?):(.*?)$/', $routeData['plugin'], $matches);
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
        if ($routeKey == '!' || $routeKey == '!/') {
            $serviceRoute = $route;
        } elseif ($routeKey[0] == '!') {
			$serviceRoute = preg_replace('/^!/', '', $routeKey);
			if ($serviceRoute != '') {
				$serviceRoute = (explode($serviceRoute, $route))[1];
				$serviceRoute = preg_replace('/^\//', '', $serviceRoute);
			}
		}
        if ($serviceRoute == '') {
            $serviceRoute = '/';
        }

		return $serviceRoute;
	}

	private function validateRouteKey(string $routeKey, string $route): bool
	{
        if ($routeKey == '!' || $routeKey == '!/') {
            return true;
        }

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
		if (isset($routeData['on-mode']) && !lx::$app->isMode($routeData['on-mode'])) {
			return false;
		}

		return true;
	}
}
