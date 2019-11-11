<?php

namespace lx;

class User extends ApplicationComponent
{
	/** @var null|string */
	protected $userModelClass = null;

	/** @var null|string */
	private $authFieldName = null;
	/** @var null|ModelInterface */
	private $userModel = null;

	public function __construct($owner, $config = [])
	{
		parent::__construct($owner, $config);
		if ($this->userModelClass) {
			$this->userModel = new $this->userModelClass();
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
		return $this->userModelClass !== null && $this->userModel !== null;
	}

	public function set($userData)
	{
		if ( ! $this->isAvailable()) {
			return;
		}

		$this->userModel->setData($userData);
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
