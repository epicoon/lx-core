<?php

namespace lx;

/**
 * @property-read Service|null $service
 * @property-read Plugin|null $plugin
 */
class Respondent extends Resource
{
	private ?Plugin $_plugin = null;

	public function __construct(iterable $config = [])
	{
		parent::__construct($config);

		$this->_plugin = $config['plugin'] ?? null;
	}

	/**
	 * @return mixed
	 */
	public function __get(string $name)
	{
		if ($name == 'plugin') return $this->getPlugin();
		if ($name == 'service') return $this->getService();

		return parent::__get($name);
	}

	public static function getDependenciesConfig(): array
	{
	    return array_merge(parent::getDependenciesConfig(), [
	        'plugin' => Plugin::class,
        ]);
	}

	protected static function getOwnMethodsList(): array
	{
		return array_merge(parent::getOwnMethodsList(), [
			'getPlugin',
			'getService',
			'getRootPlugin',
			'getRootService',
		]);
	}

	public function getPlugin(): ?Plugin
	{
		return $this->_plugin;
	}

	public function getService(): ?Service
	{
        $plugin = $this->getPlugin();
        return $plugin ? $plugin->getService() : null;
	}

	public function getRootPlugin(): ?Plugin
	{
        $plugin = $this->getPlugin();
		return $plugin ? $plugin->getRootPlugin() : null;
	}

	public function getRootService(): ?Service
	{
        $plugin = $this->getPlugin();
        return $plugin ? $plugin->getRootService() : null;
	}
}
