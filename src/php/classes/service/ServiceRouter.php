<?php

namespace lx;

use lx;

class ServiceRouter implements FusionComponentInterface
{
	use FusionComponentTrait;

	public function getService(): Service
	{
		return $this->owner;
	}

	public function route(array $serviceRouteData): ?ResourceContext
	{
		return $this->determineRouteData($serviceRouteData);
	}

	public function getMap(): array
	{
		$map = $this->getService()->getConfig('routes');
		return $map ?? [];
	}

	/**
	 * Map example:
	 * [
	 *     'route/a' => [
	 *         'controller' => 'psrTest\controller\MainController',
	 *         'method' => 'get',
	 *         'on-mode' => 'dev',
	 *         'on-service-mode' => 'someMode',
	 *     ],
	 *
	 *     'route/b' => 'psrTest\controller\SomeController',
	 *
	 *     'route/c' => 'psrTest\controller\SomeController::someAction',
	 *     'route/d' => ['action' => 'psrTest\action\SomeAction'],
	 *
	 *     'route/e' => ['plugin' => 'somePlugin'],
	 * ]
	 */
	protected function determineRouteData(array $routeData): ?ResourceContext
	{
		$resource = $routeData;
		if (isset($resource['route'])) {
			$route = $resource['route'];
			unset($resource['route']);

			$map = $this->getMap();
			if (!array_key_exists($route, $map)) {
				return null;
			}

			$data = $map[$route];
            $alowed = (is_array($data))
                ? $this->validateConditions($data)
                : true;
			if (!$alowed) {
				return null;
			}

			if (is_string($data)) {
				$data = ['controller' => $data];
			}

			if (is_array($data)) {
				$info = $data['controller'] ?? $data['action'] ?? null;
				if ($info) {
					$arr = $this->getControllerData($info);
					if (!$arr) {
						return null;
					}
					$resource['class'] = $arr[0];
					$resource['method'] = $arr[1];
				} elseif (isset($data['plugin'])) {
					if (!$this->getService()->pluginExists($data['plugin'])) {
						return null;
					}
					$resource['plugin'] = $data['plugin'];
				}
			}
		}

		return new ResourceContext($resource);
	}

	protected function getControllerData(string $nameWithAction): ?array
	{
		$arr = explode('::', $nameWithAction);
		$className = $arr[0];
		$actionMethod = $arr[1] ?? Resource::DEFAULT_RESOURCE_METHOD;

		if (ClassHelper::exists($className) && method_exists($className, $actionMethod)) {
			return [$className, $actionMethod];
		}

		return null;
	}

	private function validateConditions(array $data): bool
	{
		if (isset($data['on-mode'])) {
			if (!lx::$app->isMode($data['on-mode'])) {
				return false;
			}
		}

		if (isset($data['on-service-mode'])) {
			if (!$this->getService()->isMode($data['on-service-mode'])) {
				return false;
			}
		}

		return true;
	}
}
