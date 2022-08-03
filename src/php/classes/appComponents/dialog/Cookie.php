<?php

namespace lx;

class Cookie implements FusionComponentInterface
{
    use FusionComponentTrait;

    private DataObject $data;

	public function __construct()
	{
        $this->data = new DataObject($_COOKIE);
    }

    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->data->$name;
    }

	/**
	 * @param mixed $val
	 */
	public function __set(string $prop, $val): void
	{
	    $this->set($prop, $val);
	}

	/**
	 * @param mixed $val
	 */
	public function set(string $name, $val, ?int $expire = null): void
	{
		if ($val === null) {
			$this->data->drop($name);
		} else {
		    if ($expire === null) {
                setcookie($name, $val);
            } else {
                setcookie($name, $val, $expire);
            }

            $this->data->$name = $val;
		}
	}

	public function drop(string $name): void
	{
		$this->data->extract($name);
		setcookie($name, '', time() - 1);
	}

    /**
     * @param array|string $names
     * @param mixed $default
     * @return mixed
     */
    public function getFirstDefined($names, $default = null)
    {
        return $this->data->getFirstDefined($names, $default);
    }
}
