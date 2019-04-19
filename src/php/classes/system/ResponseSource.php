<?php

namespace lx;

class ResponseSource {
	private $data;

	/**
	 *
	 * */
	public function __construct($data) {
		$this->data = $data;

	}

	/**
	 *
	 * */
	public function isModule() {
		return isset($this->data['module']);
	}

	/**
	 *
	 * */
	public function getService() {
		return \lx::getService($this->data['service']);
	}

	/**
	 *
	 * */
	public function getModule() {
		if ($this->isModule()) {
			return $this->getService()->getModule($this->data['module']);
		}

		return null;
	}

	/**
	 *
	 * */
	public function getClassAndMethod() {
		return [
			$this->data['class'],
			$this->data['method']
		];
	}	
}
