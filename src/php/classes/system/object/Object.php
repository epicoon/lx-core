<?php

namespace lx;

class Object
{
	private static $traits = [];
	private static $traitMap = [];

	public function __construct($config = [])
	{
		$traits = self::getTraitMap();
		foreach ($traits as $traitName) {
			$trait = self::getTraitInfo($traitName);
			if (array_key_exists('__construct', $trait)) {
				$this->{$trait['__construct']}($config);
			}
		}
	}

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

	private static function getTraitMap()
	{
		if (empty(self::$traitMap) || !array_key_exists(static::class, self::$traitMap)) {
			$className = static::class;
			self::$traitMap[$className] = ClassHelper::getTraitNames($className, true);
		}

		return self::$traitMap[static::class];
	}

	private static function getTraitInfo($traitName)
	{
		if ( ! array_key_exists($traitName, self::$traits)) {
			self::loadTraitInfo($traitName);
		}

		return self::$traits[$traitName];
	}

	private static function loadTraitInfo($traitName)
	{
		$trait = new \ReflectionClass($traitName);
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
