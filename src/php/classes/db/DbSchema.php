<?php

namespace lx;

/**
 * @deprecated
 * Class DbSchema
 * @package lx
 */
class DbSchema
{
	private $pkName;
	private $fields;
	private $types;
	private $defaults;
	private $notNull;
	
	public function __construct($config)
	{
		$this->pkName = $config['pk'] ?? '';
		$this->fields = $config['fields'] ?? [];
		$this->types = $config['types'] ?? [];
		$this->defaults = $config['defaults'] ?? [];
		$this->notNull = $config['notNull'] ?? [];
	}
	
	public function hasField($name)
	{
		return in_array($name, $this->fields);
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getTypes()
	{
		return $this->types;
	}
	
	public function getType($name)
	{
		return $this->types[$name] ?? null;
	}
}
