<?php

namespace lx;

/**
 * @property-read Service $service
 * @property-read Plugin $plugin
 */
class Respondent extends Resource
{
	private Plugin $_plugin;

	public function __construct(array $config)
	{
		parent::__construct($config);

		$this->_plugin = $config['plugin'];
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
	        'plugin' => [
                'require' => true,
                'instance' => Plugin::class,
            ]
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

	public function getPlugin(): Plugin
	{
		return $this->_plugin;
	}

	public function getService(): Service
	{
		return $this->getPlugin()->getService();
	}

	public function getRootPlugin(): Plugin
	{
		return $this->getPlugin()->getRootPlugin();
	}

	public function getRootService(): Service
	{
		return $this->getPlugin()->getRootService();
	}
}
