<?php

namespace lx;

class ComponentList {
	private $fusion;
	private $config = [];
	private $list = [];

	public function __construct($fusion)
	{
		$this->fusion = $fusion;
	}

	public function __get($name) {
		if (array_key_exists($name, $this->list)) {
			return $this->list[$name];
		}

		if (array_key_exists($name, $this->config)) {
			$data = $this->config[$name];
			unset($this->config[$name]);
			$this->list[$name] = new $data['class']($this->fusion, $data['params']);
			return $this->list[$name];
		}

		return parent::__get($name);
	}

	public function has($name) {
		return array_key_exists($name, $this->list) || array_key_exists($name, $this->config);
	}

	public function load($list, $defaults = []) {
		$fullList = $list + $defaults;
		foreach ($fullList as $name => $config) {
			if (!$config) {
				continue;
			}

			$data = ClassHelper::prepareConfig($config);
			if ( ! $data) {
				throw new \Exception("Component $name not found", 400);
			}

			$this->config[$name] = $data;
		}
	}
}
