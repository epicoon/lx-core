<?php

namespace lx;

/**
 * Class BaseObject
 * @package lx
 */
class BaseObject
{
	/** @var array */
	private static $traits = [];

	/** @var array */
	private static $traitMap = [];

	/** @var array */
	private $delegateList = [];

	/**
	 * BaseObject constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		if ($this->validateConfig($config)) {
			$traits = self::getTraitMap();
			foreach ($traits as $traitName) {
				$trait = self::getTraitInfo($traitName);
				if (array_key_exists('__construct', $trait)) {
					$this->{$trait['__construct']}($config);
				}
			}
		}
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		$traits = self::getTraitMap();
		foreach ($traits as $traitName) {
			$trait = self::getTraitInfo($traitName);
			if (array_key_exists('__get', $trait)) {
				$result = $this->{$trait['__get']}($name);
				if ($result !== null) {
					return $result;
				}
			}
		}

		if (ClassHelper::publicPropertyExists($this, $name)) {
			return $this->$name;
		}
		return null;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 * @return mixed|null
	 */
	public function __call($name, $arguments)
	{
		if (ClassHelper::publicMethodExists($this, $name)) {
			return call_user_func_array([$this, $name], $arguments);
		}

		foreach ($this->delegateList as $field) {
			if (!property_exists($this, $field)) {
				continue;
			}

			$value = $this->$field;
			if (!is_object($value)) {
				continue;
			}

			if (ClassHelper::publicMethodExists($value, $name)) {
				return call_user_func_array([$value, $name], $arguments);
			}
		}

		\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
			'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
			'msg' => "Method '$name' does not exist",
			'origin_class' => static::class,
		]);
		return null;
	}

	/**
	 * @return array
	 */
	public static function getConfigProtocol()
	{
		return [];
	}

	/**
	 * @return array
	 */
	public static function diMap()
	{
		return [];
	}

	/**
	 * @param array|string $fields
	 */
	protected function delegateMethodsCall($fields)
	{
		if (is_string($fields)) {
			$fields = [$fields];
		}

		if (is_array($fields)) {
			$this->delegateList = $fields;
		}
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param array $config
	 * @return bool
	 */
	private function validateConfig($config)
	{
		$protocol = static::getConfigProtocol();
		if (empty($protocol)) {
			return true;
		}

		foreach ($protocol as $paramName => $paramDescr) {
			$required = $paramDescr['required'] ?? false;
			$instance = is_string($paramDescr)
				? $paramDescr
				: ($paramDescr['instance'] ?? null);

			if ( ! array_key_exists($paramName, $config)) {
				if ($required) {
					$className = static::class;
					\lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
						'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
						'msg' => "Class '$className' require '$paramName' parameter",
					]);
					return false;
				}

				continue;
			}

			if ($instance) {
				$param = $config[$paramName];
				if (!($param instanceof $instance)) {
					$className = static::class;
					\lx::devLog(['_'=>[__FILE__,__CLASS__,__TRAIT__,__METHOD__,__LINE__],
						'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
						'msg' => "Class '$className' has gotten wrong parameter instance for '$paramName'",
					]);
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * @return array
	 */
	private static function getTraitMap()
	{
		if (empty(self::$traitMap) || !array_key_exists(static::class, self::$traitMap)) {
			$className = static::class;
			self::$traitMap[$className] = ClassHelper::getTraitNames($className, true);
		}

		return self::$traitMap[static::class];
	}

	/**
	 * @param string $traitName
	 * @return array
	 */
	private static function getTraitInfo($traitName)
	{
		if ( ! array_key_exists($traitName, self::$traits)) {
			self::loadTraitInfo($traitName);
		}

		return self::$traits[$traitName];
	}

	/**
	 * @param string $traitName
	 */
	private static function loadTraitInfo($traitName)
	{
		try {
			$trait = new \ReflectionClass($traitName);
		} catch (\ReflectionException $e) {
			return;
		}

		if (isset($trait)) {
			self::$traits[$traitName] = [];
			$methods = $trait->getMethods();
			foreach ($methods as $method) {
				$doc = $method->getDocComment();
				if ($doc) {
					preg_match_all('/@magic +([^\s]+?)\s/', $doc, $match);
					if (empty($match[0])) {
						continue;
					}

					$type = $match[1][0];
					self::$traits[$traitName][$type] = $method->getName();
				}
			}
		}
	}
}
