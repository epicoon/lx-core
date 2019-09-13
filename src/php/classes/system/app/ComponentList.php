<?php

namespace lx;

class ComponentList extends ApplicationTool {
	private $list = [];

	public function __get($name) {
		if (array_key_exists($name, $this->list)) {
			return $this->list[$name];
		}

		return parent::__get($name);
	}

	public function has($name) {
		return array_key_exists($name, $this->list);
	}

	public function load($list, $defaults) {
		if (!$list) {
			$list = [];
		}

		foreach ($defaults as $name => $config) {
			if (array_key_exists($name, $list)) {
				continue;
			}

			if (is_string($config)) {
				$className = $config;
				$params = [];
			} elseif (is_array($config)) {
				if (isset($config['class'])) {
					$className = $config['class'];
				} elseif (isset($config[0])) {
					$className = $config[0];
				}

				if (isset($config['config'])) {
					$params = $config['config'];
				} elseif (isset($config[1])) {
					$params = $config[1];
				} else {
					$params = [];
				}
			}

			if (!isset($className) || !ClassHelper::exists($className)) {
				throw new \Exception("Component $name not found", 400);
			}

			$params['app'] = $this->app;
			$this->list[$name] = new $className($params);
		}

		foreach ($list as $name => $data) {
			if (!$data) {
				continue;
			}

			$config = ClassHelper::prepareConfig($data);

			if (!ClassHelper::exists($config['class'])) {
				throw new \Exception("Component $name not found", 400);
			}

			$config['params']['app'] = $this->app;
			$this->list[$name] = new $config['class']($config['params']);
		}
	}

	public function register($name, $class) {
		$this->list[$name] = $class;
	}
}
