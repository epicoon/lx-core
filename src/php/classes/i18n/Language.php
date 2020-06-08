<?php

namespace lx;

/**
 * Class Language
 * @package lx
 *
 * @property-read array $list
 * @property-read string $current
 * @property-read array $codes
 */
class Language implements FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var array */
	protected $_list = [];

	/** @var string */
	private $_current;

	/**
	 * Language constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
	    $this->__objectConstruct($config);

		$filePath = \lx::$conductor->lxData . '/languages';
		$file = $this->app->diProcessor->createByInterface(DataFileInterface::class, [$filePath]);

		$this->_list = $file->exists()
			? $file->get()
			: ['en-EN' => 'English'];

		$this->_current = $this->retrieveCurrentLanguage();
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'list':
				return $this->_list;
			case 'current':
				return $this->_current;
			case 'codes':
				return array_keys($this->_list);
		}

		return $this->__objectGet($name);
	}

	/**
	 * @return array
	 */
	public function getCurrentData()
	{
		return [
			'key' => $this->current,
			'name' => $this->list[$this->current],
		];
	}

	/**
	 * @return string
	 */
	protected function retrieveCurrentLanguage()
	{
		if (isset($_COOKIE['lang'])) {
			return $_COOKIE['lang'];
		}

		return $this->codes[0];
	}
}
