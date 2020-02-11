<?php

namespace lx;

trait ContextTreeTrait {
	/** @var ContextTreeTrait */
	protected $parentContext;
	protected $nestedContexts;
	protected $key;
	protected static $keyPrefix = null;
	protected static $keyCounter = 1;

	/**
	 * @magic __construct
	 * @param null $config
	 */
	public function constructContextTree($config = null) {
		$this->nestedContexts = [];

		if (is_array($config)) {
			if (isset($config['parent'])) {
				$parent = $config['parent'];
			}
			if (isset($config['key'])) {
				$key = $config['key'];
			}
		} elseif ($config instanceof ContextTreeInterface) {
			$parent = $config;
		}
		if (!isset($parent)) {
			$parent = null;
		}
		if (!isset($key)) {
			$key = $this->genUniqKey();
		}

		$this->key = $key;
		$this->setParent($parent);
	}

	public function getHead() {
		$head = $this;
		while ($head->getParent()) {
			$head = $head->getParent();
		}
		return $head;
	}

	public function getKey() {
		return $this->key;
	}

	public function setKey($key) {
		if ($this->parentContext) {
			$this->parentContext->unnest($this);
		}
		
		$this->key = $key;
		if ($this->parentContext) {
			$this->parentContext->nest($this);
		}
	}

	public function getParent() {
		return $this->parentContext;
	}

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

	public function getNested() {
		return $this->nestedContexts;
	}

	public function isHead() {
		return $this->parentContext === null;
	}

	public function add() {
		$args = func_get_args();
		$args[] = $this;

		$refClass = new \ReflectionClass(static::class);
		$instance = $refClass->newInstanceArgs($args);
		return $instance;
	}

	public function eachContext($func) {
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

	protected function nest($context) {
		$this->nestedContexts[$context->getKey()] = $context;
	}

	protected function unnest($context) {
		if (!array_key_exists($context->getKey(), $this->nestedContexts)) {
			return false;
		}
		
		unset($this->nestedContexts[$context->getKey()]);
		return true;
	}

	protected function genUniqKey() {
		$index = self::$keyCounter;
		self::$keyCounter++;
		return self::getKeyPrefix() . '_' . Math::decChangeNotation($index, 62);
	}
	
	protected static function getKeyPrefix() {
		if (self::$keyPrefix === null) {
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
