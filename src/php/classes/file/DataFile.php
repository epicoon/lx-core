<?php

namespace lx;

/**
 * You don't need note extension - the file will try to define it automatically
 *
 * Class DataFile
 * @package lx
 */
class DataFile extends File implements DataFileInterface
{
	const STYLE_LINE = 1;
	const STYLE_PREATY = 2;
	const STYLE_COMBINE = 3;

	const TYPE_PHP = 1;
	const EXTENSIONS_PHP = ['php'];

	const TYPE_JSON = 2;
	const EXTENSIONS_JSON = ['json'];

	const TYPE_YAML = 3;
	const EXTENSIONS_YAML = ['yaml', 'yml'];

	/** @var string */
	protected $extension = null;

	/** @var DataFileAdapter */
	private $adapter = null;

	/**
	 * DataFile constructor.
	 * @param string $name
	 * @param string $path
	 */
	public function __construct($name, $path = null)
	{
		parent::__construct($name, $path);

		$extensions = static::getExtensions();
		preg_match_all('/\.([^.\/]+)$/', $this->path, $matches);
		if (!empty($matches[1])) {
			$extension = $matches[1][0];
			if (array_search($extension, $extensions) !== false) {
				$this->extension = $extension;
			} else {
				\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
					'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
					'msg' => "Unsupported configuration file extension '{$extension}' for '{$this->path}'",
				]);
			}
		} else {
			foreach ($extensions as $extension) {
				if (file_exists($this->path . '.' . $extension)) {
					$this->extension = $extension;
					$this->path .= '.' . $extension;
					$this->name .= '.' . $extension;
					break;
				}
			}
		}

		if ( ! $this->extension) {
			$this->extension = $extensions[0];
			$this->path .= '.' . $this->extension;
			$this->name .= '.' . $this->extension;
		}
	}

	/**
	 * @return array
	 */
	protected static function getExtensions()
	{
		return array_merge(
			self::EXTENSIONS_PHP,
			self::EXTENSIONS_JSON,
			self::EXTENSIONS_YAML
		);
	}

	/**
	 * @param string $extension
	 * @return int|null
	 */
	protected static function defineType($extension)
	{
		if (array_search($extension, self::EXTENSIONS_PHP) !== false) {
			return self::TYPE_PHP;
		}

		if (array_search($extension, self::EXTENSIONS_JSON) !== false) {
			return self::TYPE_JSON;
		}

		if (array_search($extension, self::EXTENSIONS_YAML) !== false) {
			return self::TYPE_YAML;
		}

		return null;
	}

	/**
	 * @return array
	 */
	protected static function getAdaptersMap()
	{
		return [
			self::TYPE_PHP => PhpDataFileAdapter::class,
			self::TYPE_JSON => JsonDataFileAdapter::class,
			self::TYPE_YAML => YamlDataFileAdapter::class,
		];
	}

	/**
	 * @return string
	 */
	public function getExtension()
	{
		return $this->extension;
	}

	/**
	 * @return array
	 */
	public function get()
	{
		if ( ! $this->exists()) {
			return [];
		}

		$adapter = $this->getAdapter();
		if ( ! $adapter) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Adapter for config file '{$this->path}' not found",
			]);
			return [];
		}

		return $adapter->parse();
	}

	/**
	 * @param array $data
	 * @param int $style
	 * @return File|false
	 */
	public function put($data, $style = self::STYLE_PREATY)
	{
		$adapter = $this->getAdapter();
		if ( ! $adapter) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Adapter for config file '{$this->path}' not found",
			]);
			return false;
		}

		$string = $adapter->dataToString($data, $style);
		return parent::put($string);
	}

	/**
	 * @param int $style
	 */
	public function setStyle($style)
	{
		$this->put($this->get(), $style);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 * @param array|string|null $group
	 * @param int $style
	 * @return File|false
	 */
	public function insertParam($name, $value, $group = null, $style = self::STYLE_PREATY)
	{
		$adapter = $this->getAdapter();
		if ( ! $adapter) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Adapter for config file '{$this->path}' not found",
			]);
			return false;
		}

		$data = $this->get();
		if ($group === null) {
			$data[$name] = $value;
		} else {
			if (is_string($group)) {
				$group = [$group];
			}

			$target = &$data;
			foreach ($group as $itemName) {
				if ( ! array_key_exists($itemName, $target)) {
					$target[$itemName] = [];
				}

				$target = &$target[$itemName];
			}

			$target[$name] = $value;
			unset($target);
		}

		$this->put($data, $style);
	}

	/**
	 * @return DataFileAdapter|null
	 */
	private function getAdapter()
	{
		if ($this->adapter === null) {
			$classMap = static::getAdaptersMap();
			$type = static::defineType($this->extension);
			if (array_key_exists($type, $classMap)) {
				$class = $classMap[$type];
				$this->adapter = new $class(new File($this->getPath()));
			}
		}

		return $this->adapter;
	}
}
