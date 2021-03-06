<?php

namespace lx;

/**
 * Class ServiceRouter
 * @package lx
 */
class ServiceRouter implements FusionComponentInterface
{
    use ObjectTrait;
	use FusionComponentTrait;

	/**
	 * @return Service
	 */
	public function getService()
	{
		return $this->owner;
	}

	/**
	 * @param array $serviceRouteData
	 * @return ResourceContext|false
	 */
	public function route($serviceRouteData)
	{
		return $this->determineRouteData($serviceRouteData);
	}

	/**
	 * @return array
	 */
	public function getMap()
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
	 *
	 * @param array $routeData
	 * @return ResourceContext|false
	 */
	protected function determineRouteData($routeData)
	{
		$resource = $routeData;
		if (isset($resource['route'])) {
			$route = $resource['route'];
			unset($resource['route']);

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
					$resource['class'] = $arr[0];
					$resource['method'] = $arr[1];
				} elseif (isset($data['plugin'])) {
					if (!$this->getService()->pluginExists($data['plugin'])) {
						return false;
					}
					$resource['plugin'] = $data['plugin'];
				}
			}
		}

		return new ResourceContext($resource);
	}

	/**
	 * @param string $nameWithAction
	 * @return array|false
	 */
	protected function getControllerData($nameWithAction)
	{
		$arr = explode('::', $nameWithAction);
		$className = $arr[0];
		$actionMethod = $arr[1] ?? 'run';

		if (ClassHelper::exists($className) && method_exists($className, $actionMethod)) {
			return [$className, $actionMethod];
		}

		return false;
	}

	/**
	 * @param array $data
	 * @return bool
	 */
	private function validateConditions($data)
	{
		if (!is_array($data)) {
			return true;
		}

		if (isset($data['on-mode'])) {
			if (!$this->getService()->app->isMode($data['on-mode'])) {
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
