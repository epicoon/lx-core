<?php

namespace lx;

/**
 * Interface ErrorCollectorInterface
 * @package lx
 */
interface ErrorCollectorInterface
{
	/**
	 * @return bool
	 */
	public function hasErrors();
	
	/**
	 * @param $errorInfo string|array
	 */
	public function addError($errorInfo);

	/**
	 * @param $errors array|ErrorCollectorInterface
	 */
	public function addErrors($errors);

	/**
	 * @return array
	 */
	public function getErrors();

	/**
	 * @return null|string|ToStringConvertableInterface
	 */
	public function getFirstError();
}
