<?php

namespace lx;

/**
 * Trait ErrorCollectorTrait
 * @package lx
 */
trait ErrorCollectorTrait
{
	/** @var ErrorCollectorList */
	private $errorCollectorList = null;

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		if ($this->errorCollectorList === null) {
			return false;
		}

		return $this->errorCollectorList->hasErrors();
	}

	/**
	 * @param string|array $errorInfo
	 */
	public function addError($errorInfo)
	{
		$this->getErrorCollectorList()->addError($errorInfo);
	}

	/**
	 * @param array|ErrorCollectorInterface $errors
	 */
	public function addErrors($errors)
	{
	    if (is_string($errors)) {
	        $this->addError($errors);
        } elseif (is_array($errors)) {
			foreach ($errors as $error) {
				$this->addError($error);
			}
		} elseif ($errors instanceof ErrorCollectorInterface) {
			$list = $errors->getErrors();
			/** @var ErrorCollectorError $item */
			foreach ($list as $item) {
				$this->addError($item->getInfo());
			}
		}
	}

	/**
	 * @return array
	 */
	public function getErrors()
	{
		return $this->getErrorCollectorList()->getErrors();
	}

	/**
	 * @return ErrorCollectorError|null
	 */
	public function getFirstError()
	{
		return $this->getErrorCollectorList()->getFirstError();
	}

	/**
	 * @return ErrorCollectorList
	 */
	private function getErrorCollectorList()
	{
		if ($this->errorCollectorList === null) {
			$this->errorCollectorList = new ErrorCollectorList();
		}

		return $this->errorCollectorList;
	}
}
