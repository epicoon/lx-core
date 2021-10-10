<?php

namespace lx;

/**
 * @property-read Service $service
 */
class ServiceController extends Resource
{
	private Service $_service;

	public function __construct(iterable $config = [])
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
	
	public static function getDependenciesConfig(): array
	{
	    return array_merge(parent::getDependenciesConfig(), [
	        'service' => Service::class,
        ]);
	}
}
