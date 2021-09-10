<?php

namespace lx;

/**
 * @see ContextTreeInterface
 */
trait ContextTreeTrait
{
	private static ?string $keyPrefix = null;
	private static int $keyCounter = 1;

	protected ?ContextTreeInterface $parentContext = null;
	protected array $nestedContexts = [];
	protected string $key;
	
	/**
	 * @magic __construct
	 */
	public function constructContextTree(iterable $config = [])
	{
        if (isset($config['parent'])) {
            $parent = $config['parent'];
        }
        if (isset($config['key'])) {
            $key = $config['key'];
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

	public function getHead(): ContextTreeInterface
	{
		$head = $this;
		while ($head->getParent()) {
			$head = $head->getParent();
		}
		return $head;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function setKey(string $key): void
	{
		if ($this->parentContext) {
			$this->parentContext->unnest($this);
		}
		
		$this->key = $key;
		if ($this->parentContext) {
			$this->parentContext->nest($this);
		}
	}

	public function getParent(): ?ContextTreeInterface
	{
		return $this->parentContext;
	}

	public function setParent(ContextTreeInterface $parent): void
	{
		if ($this->parentContext) {
			$this->parentContext->unnest($this);
		}

		$this->parentContext = $parent;
		if ($parent) {
			$parent->nest($this);
		}
	}

	public function getNested(): array
	{
		return $this->nestedContexts;
	}

	public function isHead(): bool
	{
		return $this->parentContext === null;
	}

	public function add(iterable $config = []): ContextTreeInterface
	{
		$config['parent'] = $this;
		$refClass = new \ReflectionClass(static::class);
		$instance = $refClass->newInstance($config);
		return $instance;
	}

    public function nest(ContextTreeInterface $context): bool
    {
        $this->nestedContexts[$context->getKey()] = $context;
        return true;
    }

    public function unnest(ContextTreeInterface $context): bool
    {
        if (!array_key_exists($context->getKey(), $this->nestedContexts)) {
            return false;
        }

        unset($this->nestedContexts[$context->getKey()]);
        return true;
    }

	public function eachContext(callable $func): void
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

	protected function genUniqKey(): string
	{
		$index = self::$keyCounter;
		self::$keyCounter++;
		return self::getKeyPrefix() . '_' . Math::decChangeNotation($index, 62);
	}

	private static function getKeyPrefix(): string
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
