<?php

namespace lx;

class Cookie extends DataObject
{
	public function __construct()
	{
        $this->__constructArray($_COOKIE);
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
			$this->drop($name);
		} else {
		    if ($expire === null) {
                setcookie($name, $val);
            } else {
                setcookie($name, $val, $expire);
            }

			parent::__set($name, $val);
		}
	}

	public function drop(string $name): void
	{
		$this->extract($name);
		setcookie($name, '', time() - 1);
	}
}
