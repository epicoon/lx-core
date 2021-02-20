<?php

namespace lx;

/**
 * Class ServiceController
 * @package lx
 * 
 * @property-read Service $service
 */
class ServiceController extends Resource
{
	/** @var Service */
	private $_service;

	/**
	 * ServiceController constructor.
	 * @param array $config
	 */
	public function __construct($config)
	{
		parent::__construct($config);

		$this->_service = $config['service'];
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		if ($name == 'service') {
			return $this->_service;
		}

		return parent::__get($name);
	}
	
	public static function getConfigProtocol(): array
	{
	    return array_merge(parent::getConfigProtocol(), [
	        'service' => [
                'require' => true,
                'instance' => Service::class,
            ]
        ]);
	}
}
