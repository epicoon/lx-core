<?php

namespace lx;

class JsCompileDependencies
{
	private array $plugins;
	private array $modules;
	private array $scripts;
	private array $i18n;

	public function __construct(?array $data = null)
	{
		$this->plugins = isset($data['plugins']) ? array_values($data['plugins']) : [];
		$this->modules = isset($data['modules']) ? array_values($data['modules']) : [];
		$this->scripts = isset($data['scripts']) ? array_values($data['scripts']) : [];
		$this->i18n = isset($data['i18n']) ? array_values($data['i18n']) : [];
	}

	public function addPlugin(array $data): void
	{
		$this->plugins[] = $data;
	}

	public function addPlugins(array $arr): void
	{
		$this->plugins = array_merge($this->plugins, array_values($arr));
	}

	public function addModule(string $moduleName): void
	{
		$this->modules[] = $moduleName;
	}

	public function addModules(array $moduleNames): void
	{
		$this->modules = array_unique(array_merge($this->modules, array_values($moduleNames)));
	}

	public function addScript(string $path): void
	{
		$this->scripts[] = $path;
	}

	public function addScripts(array $arr): void
	{
		$this->scripts = array_unique(array_merge($this->scripts, array_values($arr)));
	}

	public function addI18n(string $config): void
	{
		$this->i18n[md5(json_encode($config))] = $config;
	}

	public function addI18ns(array $arr): void
	{
		foreach ($arr as $config) {
			$this->i18n[md5(json_encode($config))] = $config;
		}
	}

	public function add(array $map): void
	{
		$this->addPlugins($map['plugins'] ?? []);
		$this->addModules($map['modules'] ?? []);
		$this->addScripts($map['scripts'] ?? []);
		$this->addI18ns($map['i18n'] ?? []);
	}

	public function merge(JsCompileDependencies $dependencies): void
    {
        $this->add($dependencies->toArray());
    }

	public function toArray(): array
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
