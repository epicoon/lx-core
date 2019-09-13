<?php

namespace lx;

class Language extends ApplicationComponent {
	protected $_list = [];

	private $_current;

	/**
	 *
	 * */
	public function __construct($config = []) {
		parent::__construct($config);

		$filePath = $this->app->conductor->getSystemPath('lxData') . '/languages';
		$file = new ConfigFile($filePath);

		$this->_list = $file->exists()
			? $file->get()
			: ['en-EN' => 'English'];

		$this->_current = $this->retrieveCurrentLanguage();
	}

	/**
	 *
	 * */
	public function __get($name) {
		switch ($name) {
			case 'list': return $this->_list;
			case 'current': return $this->_current;
			case 'codes': return array_keys($this->_list);
		}

		return parent::__get($name);
	}

		/**
	 *
	 * */
	public function getCurrentData() {
		return [
			'key' => $this->current,
			'name' => $this->list[$this->current],
		];
	}

	/**
	 * //todo - сделать получение текущего языка изменяемым поведением
	 * */
	protected function retrieveCurrentLanguage() {
		// Пытаемся вытащить из кук
		if (isset($_COOKIE['lang'])) {
			return $_COOKIE['lang'];
		}

		// Если взять код языка неоткуда, берем первый
		return $this->codes[0];
	}
}
