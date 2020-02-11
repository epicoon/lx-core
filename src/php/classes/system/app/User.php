<?php

namespace lx;

class User extends Object implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var null|string */
	private static $userModelClass = null;

	/** @var null|string */
	private $authFieldName = null;
	/** @var null|ModelInterface */
	private $userModel = null;

	public function __construct($config = [])
	{
		parent::__construct($config);

		if (self::$userModelClass === null) {
			self::$userModelClass = $config['userModelClass'] ?? null;
		}

		if (self::$userModelClass) {
			$this->userModel = new self::$userModelClass();
		}
	}

	public function __get($name)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				return $this->userModel->$name;
			}
		}

		return parent::__get($name);
	}

	public function __set($name, $value)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				$this->userModel->$name = $value;
			}
		}
	}

	public function __call($method, $args)
	{
		if (ClassHelper::publicMethodExists($this, $method)) {
			return call_user_func_array([$this, $method], $args);
		}

		if ($this->userModel === null) {
			return null;
		}

		return $this->userModel->__call($method, $args);
	}

	public function isGuest()
	{
		if ($this->isAvailable() || $this->authFieldName) {
			$authFieldName = $this->userModel->{$this->authFieldName};
			return !($authFieldName && $authFieldName != '');
		}

		return true;
	}

	public function isAvailable()
	{
		return self::$userModelClass !== null && $this->userModel !== null;
	}

	public function set($userData)
	{
		if ( ! $this->isAvailable()) {
			return;
		}

		$this->userModel->setData($userData);
	}

	public function getAuthField()
	{
		return $this->{$this->authFieldName};
	}

	public function setAuthFieldName($name)
	{
		$this->authFieldName = $name;
	}

	public function getAuthFieldName()
	{
		if ($this->isGuest()) {
			return '';
		}

		return $this->authFieldName;
	}
}
