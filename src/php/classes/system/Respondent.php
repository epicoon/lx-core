<?php

namespace lx;

class Respondent {
	private
		$_module,
		$_db;

	public function __construct($module) {
		$this->_module = $module;
		$this->_db = null;
	}

	public function __get($name) {
		if ($name == 'db') return $this->getDb();
		if ($name == 'module') return $this->getModule();
		return null;
	}

	public function getDb() {
		if ($this->_db === null)
			$this->_db = $this->module->getService()->db();
		return $this->_db;
	}

	public function getModule() {
		return $this->_module;
	}

	public function getService() {
		return $this->getModule()->getService();
	}

	public function getModelManager($name) {
		return $this->getService()->getModelManager($name);
	}
}
