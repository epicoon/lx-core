<?php

namespace lx;

/**
 * @property-read Service $service
 */
class ServiceController extends Resource
{
	private Service $_service;

	public function __construct(array $config)
	{
		parent::__construct($config);

		$this->_service = $config['service'];
	}

	/**
	 * @return mixed
	 */
	public function __get(string $name)
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
            ],
        ]);
	}
}
