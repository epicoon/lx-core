<?php

namespace lx;

/**
 * Запрашиваемый ресурс может представлять собой либо модуль, либо экшен:
 * 1. Модуль - должен быть возвращен результат рендеригна модуля. Объект знает имя сервиса и имя модуля
 *	$this->data == ['service' => 'serviceName', 'module' => 'moduleName']
 * 2. Экшен - должен быть возвращен результат выполнения метода. Объект знает имя сервиса, класса и метода
 *	$this->data == ['service' => 'serviceName', 'class' => 'className', 'method' => 'methodName']
 * */
class ResponseSource {
	const RESTRICTION_FORBIDDEN_FOR_ALL = 5;
	const RESTRICTION_INSUFFICIENT_RIGHTS = 10;

	private $data;
	private $restrictions;

	/**
	 *
	 * */
	public function __construct($data) {
		$this->data = $data;
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
		if ($this->isModule()) {
			return $this->data['service'] . ':' . $this->data['module'];
		}

		if ($this->isObject()) {
			return get_class($this->data['object']) . '::' . $this->data['method'];
		}

		return $this->data['class'] . '::' . $this->data['method'];
	}

	/**
	 *
	 * */
	public function invoke() {
		if ($this->isObject()) {
			return $this->invokeObjectMethod();
		}

		if ($this->isStaticMethod()) {
			return $this->invokeStaticMethod();
		}

		if ($this->isAction()) {
			return $this->invokeAction();
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
	public function isModule() {
		return isset($this->data['module']);
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
	public function isStaticMethod() {
		return isset($this->data['isStatic']) ? $this->data['isStatic'] : false;
	}

	/**
	 *
	 * */
	public function getService() {
		return \lx::getService($this->data['service']);
	}

	/**
	 *
	 * */
	public function getModule() {
		if ($this->isModule()) {
			$module = $this->getService()->getModule($this->data['module']);

			if (isset($this->data['params'])) {
				$module->addParams($this->data['params']);
			}

			if (isset($this->data['clientParams'])) {
				$module->clientParams->setProperties($this->data['clientParams']);
			}

			if (isset($this->data['widgets'])) {
				$module->useWidgets($this->data['widgets']);
			}

			return $module;
		}

		return null;
	}


	/**************************************************************************************************************************
	 * PRIVATE
	 *************************************************************************************************************************/

	/**
	 *
	 * */
	private function invokeAction() {
		if (!isset($this->data['class']) || !isset($this->data['method'])) {
			return false;
		}

		$class = $this->data['class'];
		$instance = new $class($this->getService());
		$method = $this->data['method'];
		$params = isset($this->data['params']) ? $this->data['params'] : null;

		$result = $params
			? \call_user_func_array([$instance, $method], $params)
			: $instance->$method();
		
		return $result;
	}

	/**
	 *
	 * */
	private function invokeObjectMethod() {
		$object = isset($this->data['object']) ? $this->data['object'] : null;
		if (!$object) {
			return false;
		}

		$method = isset($this->data['method']) ? $this->data['method'] : null;
		if (!$method) {
			return false;
		}

		$params = isset($this->data['params']) ? $this->data['params'] : null;

		$result = $params
			? \call_user_func_array([$object, $method], $params)
			: $object->$method();
		
		return $result;
	}

	/**
	 *
	 * */
	private function invokeStaticMethod() {
		$class = isset($this->data['class']) ? $this->data['class'] : null;
		if (!$class) {
			return false;
		}

		$method = isset($this->data['method']) ? $this->data['method'] : null;
		if (!$method) {
			return false;
		}

		$params = isset($this->data['params']) ? $this->data['params'] : null;
		if (is_array($params)) {
			$result = \call_user_func_array([$class, $method], $params);
		} else {
			$re = new \ReflectionClass($class);
			$methodRe = $re->getMethod($method);
			$result = $methodRe->invoke(null);
		}

		return $result;
	}
}
