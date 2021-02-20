<?php

namespace lx;

/**
 * Class User
 * @package lx
 */
class User implements UserInterface, FusionComponentInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use FusionComponentTrait;

    /** @var ModelInterface */
    private $userModel = null;

	/** @var string */
	private $authFieldName = null;

	/**
	 * User constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
	    $this->__objectConstruct($config);
		$this->delegateMethodsCall('userModel');
	}

    public static function getConfigProtocol(): array
    {
        return [
            'userModel' => ModelInterface::class,
        ];
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

		return $this->__objectGet($name);
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
		return $this->userModel !== null;
	}

	/**
	 * TODO - need some interface for $userData?
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

	/**
	 * @return string
	 */
	public function getAuthField()
	{
		return $this->{$this->authFieldName};
	}
}
