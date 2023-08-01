<?php

namespace lx;

/**
 * You don't ought to note an extension - the file will try to define it automatically
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

	protected ?string $extension = null;
	private ?DataFileAdapter $adapter = null;

	public function __construct(string $name, ?string $path = null)
	{
		parent::__construct($name, $path);

		$extensions = static::getExtensions();
		preg_match_all('/\.([^.\/]+)$/', $this->path, $matches);
		if (!empty($matches[1])) {
			$extension = $matches[1][0];
			if (array_search($extension, $extensions, true) !== false) {
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

	protected static function getExtensions(): array
	{
		return array_merge(
			self::EXTENSIONS_PHP,
			self::EXTENSIONS_JSON,
			self::EXTENSIONS_YAML
		);
	}

	protected static function defineType(string $extension): ?int
	{
		if (array_search($extension, self::EXTENSIONS_PHP, true) !== false) {
			return self::TYPE_PHP;
		}

		if (array_search($extension, self::EXTENSIONS_JSON, true) !== false) {
			return self::TYPE_JSON;
		}

		if (array_search($extension, self::EXTENSIONS_YAML, true) !== false) {
			return self::TYPE_YAML;
		}

		return null;
	}

	protected static function getAdaptersMap(): array
	{
		return [
			self::TYPE_PHP => PhpDataFileAdapter::class,
			self::TYPE_JSON => JsonDataFileAdapter::class,
			self::TYPE_YAML => YamlDataFileAdapter::class,
		];
	}

	public function getExtension(): string
	{
		return $this->extension;
	}

	/**
	 * @return array|string
	 */
	public function get()
	{
		if ( ! $this->exists()) {
			return [];
		}

		$adapter = $this->getAdapter();
		if (!$adapter) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Adapter for config file '{$this->path}' not found",
			]);
			return [];
		}

		return $adapter->parse();
	}

    public function getText(): string
    {
        return parent::get();
    }

	/**
	 * @param array $data
	 */
	public function put($data, int $style = self::STYLE_PREATY): bool
	{
		$adapter = $this->getAdapter();
		if (!$adapter) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Adapter for config file '{$this->path}' not found",
			]);
			return false;
		}

		$string = $adapter->dataToString($data, $style);
		return parent::put($string);
	}

	public function setStyle(int $style): void
	{
		$this->put($this->get(), $style);
	}

	/**
	 * @param mixed $value
	 * @param array|string|null $group
	 */
	public function insertParam(string $name, $value, $group = null, ?int $style = self::STYLE_PREATY): bool
	{
		$adapter = $this->getAdapter();
		if (!$adapter) {
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
				if (!array_key_exists($itemName, $target)) {
					$target[$itemName] = [];
				}

				$target = &$target[$itemName];
			}

			$target[$name] = $value;
			unset($target);
		}

		$this->put($data, $style);
		return true;
	}

	private function getAdapter(): ?DataFileAdapter
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
