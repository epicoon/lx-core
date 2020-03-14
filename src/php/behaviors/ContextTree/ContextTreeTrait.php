<?php

namespace lx;

/**
 * Implementation for \lx\ContextTreeInterface
 * 
 * Trait ContextTreeTrait
 * @package lx
 */
trait ContextTreeTrait
{
	/** @var string */
	protected static $keyPrefix;

	/** @var int */
	protected static $keyCounter = 1;

	/** @var ContextTreeInterface */
	protected $parentContext;

	/** @var array */
	protected $nestedContexts;
	
	/** @var string */
	protected $key;
	
	/**
	 * @magic __construct
	 * @param array $config
	 */
	public function constructContextTree($config = [])
	{
		$this->nestedContexts = [];

		if (is_array($config)) {
			if (isset($config['parent'])) {
				$parent = $config['parent'];
			}
			if (isset($config['key'])) {
				$key = $config['key'];
			}
		}

		if (!isset($parent)) {
			$parent = null;
		}
		if (!isset($key)) {
			$key = $this->genUniqKey();
		}

		$this->key = $key;
		if ($parent) {
			$this->setParent($parent);
		}
	}

	/**
	 * @return ContextTreeInterface
	 */
	public function getHead()
	{
		$head = $this;
		while ($head->getParent()) {
			$head = $head->getParent();
		}
		return $head;
	}

	/**
	 * @return string
	 */
	public function getKey()
	{
		return $this->key;
	}

	/**
	 * @param string $key
	 */
	public function setKey($key)
	{
		if ($this->parentContext) {
			$this->parentContext->unnest($this);
		}
		
		$this->key = $key;
		if ($this->parentContext) {
			$this->parentContext->nest($this);
		}
	}

	/**
	 * @return ContextTreeInterface|null
	 */
	public function getParent()
	{
		return $this->parentContext;
	}

	/**
	 * @param ContextTreeInterface $parent
	 */
	public function setParent($parent)
	{
		if ($this->parentContext) {
			$this->parentContext->unnest($this);
		}

		$this->parentContext = $parent;
		if ($parent) {
			$parent->nest($this);
		}
	}

	/**
	 * @return array
	 */
	public function getNested()
	{
		return $this->nestedContexts;
	}

	/**
	 * @return bool
	 */
	public function isHead()
	{
		return $this->parentContext === null;
	}

	/**
	 * @param array $config
	 * @return ContextTreeInterface
	 */
	public function add($config = [])
	{
		$config['parent'] = $this;
		$refClass = new \ReflectionClass(static::class);
		$instance = $refClass->newInstance($config);
		return $instance;
	}

	/**
	 * @param callable $func
	 */
	public function eachContext($func)
	{
		$head = $this->getHead();

		$re = function($context) use ($func, &$re) {
			$nested = $context->getNested();
			foreach ($nested as $child) {
				$func($child);
				$re($child);
			}
		};

		$func($head);
		$re($head);
	}

	/**
	 * @param ContextTreeInterface $context
	 */
	protected function nest($context)
	{
		$this->nestedContexts[$context->getKey()] = $context;
	}

	/**
	 * @param ContextTreeInterface $context
	 * @return bool
	 */
	protected function unnest($context)
	{
		if (!array_key_exists($context->getKey(), $this->nestedContexts)) {
			return false;
		}
		
		unset($this->nestedContexts[$context->getKey()]);
		return true;
	}

	/**
	 * @return string
	 */
	protected function genUniqKey()
	{
		$index = self::$keyCounter;
		self::$keyCounter++;
		return self::getKeyPrefix() . '_' . Math::decChangeNotation($index, 62);
	}

	/**
	 * @return string
	 */
	protected static function getKeyPrefix()
	{
		if ( ! self::$keyPrefix) {
			self::$keyPrefix = ''
				. Math::decChangeNotation(time(), 62)
				. '_'
				. Math::decChangeNotation(Math::rand(1, 9999), 62)
				. Math::decChangeNotation(Math::rand(1, 9999), 62)
				. Math::decChangeNotation(Math::rand(1, 9999), 62)
			;
		}
		
		return self::$keyPrefix;
	}
}
