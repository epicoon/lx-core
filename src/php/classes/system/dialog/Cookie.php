<?php

namespace lx;

/**
 * Instance of this class is available as [[\lx::$app->dialog->getCookie()]]
 *
 * Class Cookie
 * @package lx
 */
class Cookie extends DataObject
{
	/**
	 * Cookie constructor.
	 */
	public function __construct()
	{
		$this->setProperties($_COOKIE);
	}

	/**
	 * @param string $prop
	 * @param string|int|double $val
	 */
	public function __set($prop, $val)
	{
		if ($val === null) {
			$this->drop($prop);
		} else {
			setcookie($prop, $val);
			parent::__set($prop, $val);
		}
	}

	/**
	 * @param string $name
	 * @param string|int|double $val
	 * @param int $expire
	 */
	public function set($name, $val, $expire)
	{
		if ($val === null) {
			$this->drop($name);
		} else {
			setcookie($name, $val, $expire);
			parent::__set($name, $val);
		}
	}

	/**
	 * @param string $name
	 */
	public function drop($name)
	{
		$this->extract($name);
		setcookie($name, '', time() - 1);
	}
}
