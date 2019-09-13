<?php

namespace lx;

trait ContextTreeTrait {
	protected $parentContext;
	protected $nestedContexts;
	protected $key;

	public function ContextTreeTrait($parent = null) {
		$this->parentContext = $parent;
		$this->nestedContexts = [];
		$this->key = $this->genUniqKey();
		if ($parent) {
			$parent->nest($this);
		}
	}

	public function getKey() {
		return $this->key;
	}

	public function getHead() {
		$head = $this;
		while ($head->getParent()) {
			$head = $head->getParent();
		}
		return $head;
	}

	public function getParent() {
		return $this->parentContext;
	}

	public function getNested() {
		return $this->nestedContexts;
	}

	public function nest($context) {
		$this->nestedContexts[$context->getKey()] = $context;
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

	protected function genUniqKey() {
		$randKey = function() {
			return
				Math::decChangeNotation(Math::rand(0, 255), 16).
				Math::decChangeNotation(Math::rand(0, 255), 16).
				Math::decChangeNotation(Math::rand(0, 255), 16);
		};
		$isUniq = function($key) {
			$match = false;
			$this->eachContext(function($context) use ($key, &$match) {
				if ($context->getKey() == $key) {
					$match = true;
				}
			});
			return !$match;
		};
		do {
			$uniqRand = $randKey();
		} while (!$isUniq($uniqRand));

		return $uniqRand;;
	}
}
