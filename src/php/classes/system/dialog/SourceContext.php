<?php

namespace lx;

/**
 * Requested source is plugin or action:
 * 1. Plugin - will be returned plugin rendering result.
 * Object need to know service name and plugin name.
 *	$this->data == ['service' => 'serviceName', 'plugin' => 'pluginName']
 * 2. Action - will be returned result of a method invoke.
 * Object need to know action class name and method name. It can also know service name.
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 *
 * Class SourceContext
 * @package lx
 */
class SourceContext extends BaseObject
{
	use ApplicationToolTrait;

	/** @var array */
	private $data;

	/** @var Plugin */
	private $plugin;

	/**
	 * SourceContext constructor.
	 * @param array $data
	 */
	public function __construct($data = [])
	{
		$this->setData($data);
	}

	/**
	 * @param array $data
	 */
	public function setData($data)
	{
		$this->data = $data;
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return mixed|SourceError
	 */
	public function invoke($methodName = null, $params = null)
	{
		if ($this->isAction()) {
			return $this->invokeAction($methodName, $params);
		}

		if ($this->isPlugin()) {
			return $this->invokePlugin($methodName, $params);
		}

		return new SourceError(ResponseCodeEnum::NOT_FOUND);
	}

	/**
	 * @return bool
	 */
	public function isAction()
	{
		$data = $this->data;
		return (
			(isset($data['class']) || isset($data['object']))
			&&
			isset($data['method'])
		);
	}

	/**
	 * @return bool
	 */
	public function isPlugin()
	{
		return isset($this->data['plugin']);
	}

	/**
	 * @return Service|null
	 */
	public function getService()
	{
		if (isset($this->data['service'])) {
			return $this->app->getService($this->data['service']);
		}

		return null;
	}

	/**
	 * @return Plugin|null
	 */
	public function getPlugin()
	{
		if ( ! $this->isPlugin()) {
			return null;
		}

		if ( ! $this->plugin) {
			$plugin = $this->getService()->getPlugin($this->data['plugin']);

			if (isset($this->data['params'])) {
				$plugin->addParams($this->data['params']);
			}

			if (isset($this->data['dependencies'])) {
				$plugin->addDependencies($this->data['dependencies']);
			}

			$this->plugin = $plugin;
		}

		return $this->plugin;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return mixed|SourceError
	 */
	private function invokeAction($methodName, $params)
	{
		$object = $this->getObject();
		if (!$object) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName || !method_exists($object, $methodName)) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$params = $params ?? $this->data['params'] ?? [];

		return $object->runAction($methodName, $params);
	}

	/**
	 * @param string $methodName
	 * @param array $params
	 * @return mixed|SourceError
	 */
	private function invokePlugin($methodName, $params)
	{
		$plugin = $this->getPlugin();
		$methodName = $methodName ?? Plugin::DEFAULT_SOURCE_METHOD;
		if (!method_exists($plugin, $methodName)) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$params = $params ?? $this->data['params'] ?? [];
		return $plugin->runAction($methodName, $params);
	}

	/**
	 * @return SourceInterface|null
	 */
	private function getObject()
	{
		$object = null;
		if (isset($this->data['object'])) {
			$object = $this->data['object'];
		} elseif (isset($this->data['class'])) {
			$class = $this->data['class'];
			$config = [];
			if (isset($this->data['service'])) {
				$config['service'] = $this->getService();
			}
			$object = $this->app->diProcessor->create($class, $config);
		}

		if ($object && $object instanceof SourceInterface) {
			return $object;
		}

		return null;
	}
}
