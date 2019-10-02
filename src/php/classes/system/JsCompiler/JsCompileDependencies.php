<?php

namespace lx;

class JsCompileDependencies {
	private $plugins;
	private $modules;
	private $scripts;
	private $i18n;
	
	public function __construct($data = null) {
		$this->plugins = $data['plugins'] ?? [];
		$this->modules = $data['modules'] ?? [];
		$this->scripts = $data['scripts'] ?? [];
		$this->i18n = $data['i18n'] ?? [];
	}

	public function addPlugin($data) {
		$this->plugins[] = $data;
	}

	public function addPlugins($arr) {
		$this->plugins = array_unique(array_merge($this->plugins, $arr));
	}

	public function addModule($moduleName) {
		$this->modules[] = $moduleName;
	}

	public function addModules($moduleNames) {
		$this->modules = array_unique(array_merge($this->modules, $moduleNames));
	}

	public function addScript($path) {
		$this->scripts[] = $path;
	}

	public function addScripts($arr) {
		$this->scripts = array_unique(array_merge($this->scripts, $arr));
	}

	public function addI18n($config) {
		$this->i18n[md5(json_encode($config))] = $config;
	}

	public function addI18ns($arr) {
		foreach ($arr as $config) {
			$this->i18n[md5(json_encode($config))] = $config;
		}
	}

	/**
	 * @param $obj JsCompileDependencies
	 */
	public function add($obj) {
		if (is_array($obj)) {
			$arr = $obj;
		} elseif (method_exists($obj, 'toArray')) {
			$arr = $obj->toArray();
		} else {
			$arr = [];
		}
		if (isset($arr['plugins'])) $this->addPlugins($arr['plugins']);
		if (isset($arr['modules'])) $this->addModules($arr['modules']);
		if (isset($arr['scripts'])) $this->addScripts($arr['scripts']);
		if (isset($arr['i18n'])) $this->addI18ns($arr['i18n']);
	}

	/**
	 * @return array
	 */
	public function toArray() {
		$result = [];
		if (!empty($this->plugins)) {
			$result['plugins'] = $this->plugins;
		}
		if (!empty($this->modules)) {
			$result['modules'] = $this->modules;
		}
		if (!empty($this->scripts)) {
			$result['scripts'] = $this->scripts;
		}
		if (!empty($this->i18n)) {
			$result['i18n'] = $this->i18n;
		}
		return $result;
	}
}
