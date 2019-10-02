<?php

namespace lx;

class PlatformConductor {
	private
		// массив псевдонимов путей
		$publicFields = [],

		// ключевые соглашения в путях
		$_sitePath,
		$_systemPath,
		$_autoloadMap,

		$_clientConfig,
		$_lxFiles,
		$_system,
		$_systemTemp,
		$_lxData,

		$_lx,
		$_core,
		$_phpCore,
		$_jsCore,
		$_lxWidgets,
		$_stdResponses,

		$_packageLxConfig,
		$_packageConfig,

		$_defaultServiceConfig,
		$_defaultPluginConfig;

	public function __construct() {
		$this->_sitePath = dirname(__DIR__, 5);
		$this->_systemPath = $this->_sitePath . '/lx/.system';

		$this->_clientConfig = $this->_sitePath . '/lx/config';
		$this->_lxFiles = $this->_sitePath . '/lx';
		$this->_system = $this->_lxFiles . '/.system';
		$this->_systemTemp = $this->_system . '/temp';
		$this->_lxData = $this->_lxFiles . '/data';

		$this->_lx = $this->_sitePath . '/vendor/lx/lx-core';
		$this->_core = $this->_lx . '/src';
		$this->_phpCore = $this->_core . '/php';
		$this->_jsCore = $this->_core . '/js/app.js';
		$this->_lxWidgets = $this->_core . '/widgets';
		$this->_stdResponses = $this->_core . '/php/stdResponses';

		$this->_packageLxConfig = ['lx-config.php', 'lx-config/main.php', 'lx-config.yaml', 'lx-config/main.yaml'];
		$this->_packageConfig = array_merge($this->_packageLxConfig, ['composer.json']);

		$this->_defaultServiceConfig = $this->_clientConfig . '/service.php';
		$this->_defaultPluginConfig = $this->_clientConfig . '/plugin.php';

		$this->publicFields = [
			'core',
			'sitePath',
			'lxFiles',
			'lxData',
		];
	}

	/**
	 * Геттер предоставляет доступ к полям, начинающимся с '_'
	 */
	public function __get($name) {
		if ($name == 'site') {
			return $this->_sitePath;
		}

		if (in_array($name, $this->publicFields)) {
			return $this->{'_' . $name};
		} elseif ($name == 'pluginConfig') {
			return $this->_packageLxConfig;
		} elseif ($name == 'appConfig') {
			$config = $this->_clientConfig;
			if (file_exists($config . '/main.php')) {
				return $config . '/main.php';
			}
			if (file_exists($config . '/main.yaml')) {
				return $config . '/main.yaml';
			}
			return null;
		}

		return false;
	}

	public function getRootPath() {
		return $this->_sitePath;
	}

	/**
	 *
	 * */
	public function getSystemPath($name) {
		if (property_exists($this, '_' . $name)) {
			return $this->{'_' . $name};
		}

		return false;
	}

	public function getTempFile($extension = null) {
        $tempPath = \lx::$conductor->getSystemPath('systemTemp')
            . '/'
            . Math::randHash()
            . ($extension ? ('.' . $extension) : '');
        return new File($tempPath);
	}
}
