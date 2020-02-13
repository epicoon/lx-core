<?php

namespace lx;

class Respondent extends Source {
	private
		$_plugin,

		//TODO ?????????????????????????????????????????
		$_db;

	public function __construct($config) {
		parent::__construct($config);

		$this->_plugin = $config['plugin'];
		$this->_db = null;
	}

	public function __get($name) {
		if ($name == 'plugin') return $this->getPlugin();
		if ($name == 'service') return $this->getService();

		return parent::__get($name);
	}

	/**
	 * @return array
	 */
	public static function getConfigProtocol()
	{
		$protocol = parent::getConfigProtocol();
		$protocol['plugin'] = [
			'require' => true,
			'instance' => Plugin::class,
		];
		return $protocol;
	}

	//TODO ?????????????????????????
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

	public function getRootPlugin() {
		return $this->getPlugin()->getRootPlugin();
	}

	public function getRootService() {
		return $this->getPlugin()->getRootService();
	}

	public function getModelManager($name) {
		return $this->getService()->getModelManager($name);
	}
}
