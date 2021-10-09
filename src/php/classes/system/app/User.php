<?php

namespace lx;

class User implements UserInterface, FusionComponentInterface
{
	use FusionComponentTrait;

    private ?ModelInterface $userModel = null;
    private ?string $authFieldName = null;

	public function __construct(iterable $config = [])
	{
	    $this->__objectConstruct($config);
		$this->delegateMethodsCall('userModel');
	}
	
	/**
	 * @return mixed
	 */
	public function __get(string $name)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				return $this->userModel->getField($name);
			}

            if ($this->userModel->hasRelation($name)) {
                return $this->userModel->getRelated($name);
            }
		}

		return $this->__objectGet($name);
	}

	/**
	 * @param mixed $value
	 */
	public function __set(string $name, $value)
	{
		if ($this->userModel) {
			if ($this->userModel->hasField($name)) {
				$this->userModel->setField($name, $value);
			}

            if ($this->userModel->hasRelation($name)) {
                $this->userModel->setRelated($name, $value);
            }
		}
	}
	
	public function isGuest(): bool
	{
	    return ($this->userModel === null);
	}
	
    public function setModel(ModelInterface $userModel): void
    {
        $this->userModel = $userModel;
    }

    public function getModel(): ?ModelInterface
    {
        return $this->userModel;
    }

    public function setAuthFieldName(string $name): void
    {
        $this->authFieldName = $name;
    }

    public function getAuthFieldName(): string
    {
        if ($this->isGuest()) {
            return '';
        }

        return $this->authFieldName;
    }

	public function getAuthValue(): string
	{
	    if ($this->isGuest()) {
	        return '';
        }
	    
		return $this->userModel->getField($this->authFieldName);
	}
}
