<?php

namespace lx;

/**
 * Запрашиваемый ресурс может представлять собой либо плагин, либо экшен:
 * 1. Модуль - должен быть возвращен результат рендеригна плагина. Объект знает имя сервиса и имя плагина
 *	$this->data == ['service' => 'serviceName', 'plugin' => 'pluginName']
 * 2. Экшен - должен быть возвращен результат выполнения метода. Объект знает имя сервиса, класса и метода
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 * */
class ResponseSource {
	use ApplicationToolTrait;

	const RESTRICTION_FORBIDDEN_FOR_ALL = 5;
	const RESTRICTION_INSUFFICIENT_RIGHTS = 10;

	private $data;
	private $plugin;
	private $restrictions;

	/**
	 *
	 * */
	public function __construct($data = []) {
		$this->setData($data);
		$this->restrictions = [];
	}

	/**
	 *
	 * */
	public function setData($data) {
		$this->data = $data;
	}

	/**
	 *
	 * */
	public function addRestriction($restriction) {
		$this->restrictions[] = $restriction;
	}

	/**
	 *
	 * */
	public function hasRestriction() {
		return !empty($this->restrictions);
	}

	/**
	 *
	 * */
	public function getRestriction() {
		return $this->restrictions[0];
	}

	/**
	 *
	 * */
	public function getSourceName() {
		if ($this->isPlugin()) {
			return $this->data['service'] . ':' . $this->data['plugin'];
		}

		if ($this->isObject()) {
			return get_class($this->data['object']) . '::' . $this->data['method'];
		}

		return $this->data['class'] . '::' . $this->data['method'];
	}

	/**
	 *
	 * */
	public function invoke($methodName = null, $params = null) {
		if ($this->isObject()) {
			return $this->invokeObjectMethod($methodName, $params);
		}

		if ($this->isWidget()) {
			return $this->invokeWidgetMethod($methodName, $params);
		}

		if ($this->isStaticMethod()) {
			return $this->invokeStaticMethod($methodName, $params);
		}

		if ($this->isAction()) {
			return $this->invokeAction($methodName, $params);
		}

		if ($this->isPlugin()) {
			return $this->invokePluginMethod($methodName, $params);
		}

		return false;
	}

	/**
	 *
	 * */
	public function isAction() {
		return isset($this->data['action']) ? $this->data['action'] : false;
	}

	/**
	 *
	 * */
	public function isPlugin() {
		return isset($this->data['plugin']);
	}

	/**
	 *
	 * */
	public function isObject() {
		return isset($this->data['object']);
	}

	/**
	 *
	 * */
	public function isWidget() {
		return isset($this->data['isWidget']) ? $this->data['isWidget'] : false;
	}

	/**
	 *
	 * */
	public function isStaticMethod() {
		return isset($this->data['isStatic']) ? $this->data['isStatic'] : false;
	}

	/**
	 *
	 * */
	public function getService() {
		return $this->app->getService($this->data['service']);
	}

	/**
	 *
	 * */
	public function getPlugin() {
		if ( ! $this->isPlugin()) {
			return null;
		}

		if ( ! $this->plugin) {
			$plugin = $this->getService()->getPlugin($this->data['plugin']);

			if (isset($this->data['renderParams'])) {
				$plugin->addRenderParams($this->data['renderParams']);
			}

			if (isset($this->data['clientParams'])) {
				$plugin->clientParams->setProperties($this->data['clientParams']);
			}

			if (isset($this->data['dependencies'])) {
				$plugin->setDependencies($this->data['dependencies']);
			}

			$this->plugin = $plugin;
		}

		return $this->plugin;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	private function invokeAction($methodName, $params)
	{
		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!isset($this->data['class']) || !$methodName) {
			return false;
		}

		if (!method_exists($this->data['class'], $methodName)) {
			return false;
		}

		$class = $this->data['class'];
		$instance = new $class($this->getService());
		$params = $params ?? $this->data['params'] ?? null;
		return $params
			? \call_user_func_array([$instance, $methodName], $params)
			: $instance->$methodName();
	}

	private function invokePluginMethod($methodName, $params)
	{
		$plugin = $this->getPlugin();
		if (!$plugin || !$methodName || !method_exists($plugin, $methodName)) {
			return false;
		}

		return $params
			? \call_user_func_array([$plugin, $methodName], $params)
			: $plugin->$methodName();
	}

	private function invokeObjectMethod($methodName, $params)
	{
		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName) {
			return false;
		}

		$object = $this->data['object'] ?? null;
		if (!$object || !method_exists($object, $methodName)) {
			return false;
		}

		$params = $params ?? $this->data['params'] ?? null;
		return $params
			? \call_user_func_array([$object, $methodName], $params)
			: $object->$methodName();
	}

	private function invokeWidgetMethod($methodName, $params)
	{
		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName) {
			return false;
		}

		$class = $this->data['class'] ?? null;
		if (!$class || !method_exists($class, $methodName)) {
			return false;
		}

		$widget = new $class($this->app);
		$params = $params ?? $this->data['params'] ?? null;
		return $params
			? \call_user_func_array([$widget, $methodName], $params)
			: $widget->$methodName();
	}

	private function invokeStaticMethod($methodName, $params)
	{
		$methodName = $methodName ?? $this->data['method'] ?? null;
		if (!$methodName) {
			return false;
		}

		$class = $this->data['class'] ?? null;
		if (!$class || !method_exists($class, $methodName)) {
			return false;
		}

		$params = $params ?? $this->data['params'] ?? null;
		if (is_array($params)) {
			$result = \call_user_func_array([$class, $methodName], $params);
		} else {
			$re = new \ReflectionClass($class);
			$methodRe = $re->getMethod($methodName);
			$result = $methodRe->invoke(null);
		}

		return $result;
	}
}
