<?php

namespace lx;

/**
 * Class Respondent
 * @package lx
 *
 * @property-read Service $service
 * @property-read Plugin $plugin
 */
class Respondent extends Resource
{
	/** @var Plugin */
	private $_plugin;

	/**
	 * Respondent constructor.
	 * @param array $config
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->_plugin = $config['plugin'];
	}

	/**
	 * @param string $name
	 * @return Plugin|Service|mixed
	 */
	public function __get($name)
	{
		if ($name == 'plugin') return $this->getPlugin();
		if ($name == 'service') return $this->getService();

		return parent::__get($name);
	}

	public static function getConfigProtocol(): array
	{
	    return array_merge(parent::getConfigProtocol(), [
	        'plugin' => [
                'require' => true,
                'instance' => Plugin::class,
            ]
        ]);
	}

	/**
	 * @return array
	 */
	protected static function getOwnMethodsList()
	{
		return array_merge(parent::getOwnMethodsList(), [
			'getPlugin',
			'getService',
			'getRootPlugin',
			'getRootService',
		]);
	}

	/**
	 * @return Plugin
	 */
	public function getPlugin()
	{
		return $this->_plugin;
	}

	/**
	 * @return Service
	 */
	public function getService()
	{
		return $this->getPlugin()->getService();
	}

	/**
	 * @return Plugin
	 */
	public function getRootPlugin()
	{
		return $this->getPlugin()->getRootPlugin();
	}

	/**
	 * @return Service
	 */
	public function getRootService()
	{
		return $this->getPlugin()->getRootService();
	}
}
