<?php

namespace lx;

/**
 * Class User
 * @package lx
 */
class User extends BaseObject implements FusionComponentInterface
{
	use ApplicationToolTrait;
	use FusionComponentTrait;

	/** @var string */
	private static $userModelClass = null;

	/** @var string */
	private $authFieldName = null;

	/** @var ModelInterface */
	private $userModel = null;

	/**
	 * User constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (self::$userModelClass === null) {
			self::$userModelClass = $config['userModelClass'] ?? null;
		}

		if (self::$userModelClass) {
			$this->userModel = new self::$userModelClass();
		}

		$this->delegateMethodsCall('userModel');
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function __get($name)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				return $this->userModel->$name;
			}
		}

		return parent::__get($name);
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				$this->userModel->$name = $value;
			}
		}
	}

	/**
	 * @return bool
	 */
	public function isGuest()
	{
		if ($this->isAvailable() || $this->authFieldName) {
			$authFieldName = $this->userModel->{$this->authFieldName};
			return !($authFieldName && $authFieldName != '');
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function isAvailable()
	{
		return self::$userModelClass !== null && $this->userModel !== null;
	}

	/**
	 * @todo - need some interface for $userData?
	 * @param $userData
	 */
	public function set($userData)
	{
		if (!$this->isAvailable()) {
			return;
		}

		$this->userModel->setData($userData);
	}

	/**
	 * @return string
	 */
	public function getAuthField()
	{
		return $this->{$this->authFieldName};
	}

	/**
	 * @param string $name
	 */
	public function setAuthFieldName($name)
	{
		$this->authFieldName = $name;
	}

	/**
	 * @return string
	 */
	public function getAuthFieldName()
	{
		if ($this->isGuest()) {
			return '';
		}

		return $this->authFieldName;
	}
}
