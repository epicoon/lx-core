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

	protected array $_list = [];
	private string $_current;

	public function __construct(array $config = [])
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
	public function __get(string $name)
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

	public function getCurrentData(): array
	{
		return [
			'key' => $this->current,
			'name' => $this->list[$this->current],
		];
	}

	protected function retrieveCurrentLanguage(): string
	{
		if (isset($_COOKIE['lang'])) {
			return $_COOKIE['lang'];
		}

		return $this->codes[0];
	}
}
