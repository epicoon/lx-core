<?php

namespace lx;

/**
 * Class JsCompileDependencies
 * @package lx
 */
class JsCompileDependencies
{
	/** @var array */
	private $plugins;

	/** @var array */
	private $modules;

	/** @var array */
	private $scripts;

	/** @var array */
	private $i18n;

	/**
	 * JsCompileDependencies constructor.
	 * @param array $data
	 */
	public function __construct($data = null)
	{
		$this->plugins = isset($data['plugins']) ? array_values($data['plugins']) : [];
		$this->modules = isset($data['modules']) ? array_values($data['modules']) : [];
		$this->scripts = isset($data['scripts']) ? array_values($data['scripts']) : [];
		$this->i18n = isset($data['i18n']) ? array_values($data['i18n']) : [];
	}

	/**
	 * @param array $data
	 */
	public function addPlugin($data)
	{
		$this->plugins[] = $data;
	}

	/**
	 * @param array $arr
	 */
	public function addPlugins($arr)
	{
		$this->plugins = array_merge($this->plugins, array_values($arr));
	}

	/**
	 * @param string $moduleName
	 */
	public function addModule($moduleName)
	{
		$this->modules[] = $moduleName;
	}

	/**
	 * @param array $moduleNames
	 */
	public function addModules($moduleNames)
	{
		$this->modules = array_unique(array_merge($this->modules, array_values($moduleNames)));
	}

	/**
	 * @param string $path
	 */
	public function addScript($path)
	{
		$this->scripts[] = $path;
	}

	/**
	 * @param array $arr
	 */
	public function addScripts($arr)
	{
		$this->scripts = array_unique(array_merge($this->scripts, array_values($arr)));
	}

	/**
	 * @param string $config
	 */
	public function addI18n($config)
	{
		$this->i18n[md5(json_encode($config))] = $config;
	}

	/**
	 * @param array $arr
	 */
	public function addI18ns($arr)
	{
		foreach ($arr as $config) {
			$this->i18n[md5(json_encode($config))] = $config;
		}
	}

	/**
	 * @param JsCompileDependencies|array $obj
	 */
	public function add($obj)
	{
		if (is_array($obj)) {
			$arr = $obj;
		} elseif (method_exists($obj, 'toArray')) {
			$arr = $obj->toArray();
		} else {
			$arr = [];
		}

		$this->addPlugins($arr['plugins'] ?? []);
		$this->addModules($arr['modules'] ?? []);
		$this->addScripts($arr['scripts'] ?? []);
		$this->addI18ns($arr['i18n'] ?? []);
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
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
