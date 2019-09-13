<?php

namespace lx;

class Respondent {
	private
		$_plugin,
		$_db;

	public function __construct($plugin) {
		$this->_plugin = $plugin;
		$this->_db = null;
	}

	public function __get($name) {
		if ($name == 'db') return $this->getDb();
		if ($name == 'plugin') return $this->getPlugin();
		if ($name == 'app') return $this->getPlugin()->app;
		return null;
	}

	public function getDb() {
		if ($this->_db === null)
			$this->_db = $this->plugin->getService()->db();
		return $this->_db;
	}

	public function getPlugin() {
		return $this->_plugin;
	}

	public function getService() {
		return $this->getPlugin()->getService();
	}

	public function getModelManager($name) {
		return $this->getService()->getModelManager($name);
	}
}
