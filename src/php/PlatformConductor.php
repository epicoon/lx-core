<?php

namespace lx;

/**
 * Conductor classes can find different pathes in application
 * This is the most common conductor for platform
 * 
 * Class PlatformConductor
 * @package lx
 *
 * @property-read string $site
 * @property-read string $sitePath
 * @property-read string $web
 * @property-read string $webCss
 * @property-read string $webJs
 * @property-read string $core
 * @property-read string $jsCore
 * @property-read string $jsNode
 * @property-read string $lxData
 * @property-read string $stdResponses
 * @property-read string $devLog
 */
class PlatformConductor
{
	/** @var string */
	private $_sitePath;

	/** @var string */
	private $_web;

	/** @var string */
	private $_webCss;

    /** @var string */
    private $_webJs;

	/** @var string */
	private $_core;

	/** @var string */
	private $_jsCore;

	/** @var string */
	private $_jsNode;

	/** @var string */
	private $_stdResponses;

	/** @var string */
	private $_lxFiles;

	/** @var string */
	private $_system;

	/** @var string */
	private $_lxData;

	/** @var string */
	private $_clientConfig;

	/** @var string */
	private $_devLog;

	/** @var string */
	private $_defaultServiceConfig;

	/** @var string */
	private $_defaultPluginConfig;

	/** @var array */
	private $_lxPackageConfig;

	/** @var array */
	private $_packageConfig;

	/** @var array */
	private $publicFields;

	/**
	 * PlatformConductor constructor.
	 */
	public function __construct()
	{
		$this->_sitePath = dirname(__DIR__, 5);
		$this->_web = $this->_sitePath . '/web';
		$this->_webCss = $this->_web . '/css';
        $this->_webJs = $this->_web . '/js';

		$this->_core = $this->_sitePath . '/vendor/lx/core/src';
		$this->_jsCore = $this->_core . '/js/app.js';
		$this->_jsNode = $this->_core . '/js/exec.js';
		$this->_stdResponses = $this->_core . '/php/stdResponses';

		$this->_lxFiles = $this->_sitePath . '/lx';
		$this->_system = $this->_lxFiles . '/.system';
		$this->_lxData = $this->_lxFiles . '/data';
		$this->_clientConfig = $this->_lxFiles . '/config';

		$this->_devLog = $this->_system . '/dev_log';

		$this->_defaultServiceConfig = $this->_clientConfig . '/service.php';
		$this->_defaultPluginConfig = $this->_clientConfig . '/plugin.php';

		$this->_lxPackageConfig = ['lx-config', 'lx-config/main'];
		$this->_packageConfig = array_merge($this->_lxPackageConfig, ['composer.json']);
		$this->publicFields = [
			'web',
			'webCss',
            'webJs',
			'core',
			'jsCore',
			'jsNode',
			'lxData',
			'stdResponses',
			'devLog',
		];
	}

	/**
	 * @param string $name
	 * @return string|array
	 */
	public function __get($name)
	{
		if ($name == 'site' || $name == 'sitePath') {
			return $this->_sitePath;
		}

		if (in_array($name, $this->publicFields)) {
			return $this->{'_' . $name};
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->_sitePath;
	}

	/**
	 * @param $name
	 * @return string|array|bool
	 */
	public function getSystemPath($name = null)
	{
		if ($name) {
			return $this->_system . '/' . $name;
		}

		return $this->_system;
	}

	/**
	 * @return string|null
	 */
	public function getAppConfig()
	{
		$config = $this->_clientConfig;
		if (file_exists($config . '/main.php')) {
			return $config . '/main.php';
		}
		if (file_exists($config . '/main.yaml')) {
			return $config . '/main.yaml';
		}

		return null;
	}

	/**
	 * @return string
	 */
	public function getDefaultServiceConfig()
	{
		return $this->_defaultServiceConfig;
	}

	/**
	 * @return string
	 */
	public function getDefaultPluginConfig()
	{
		return $this->_defaultPluginConfig;
	}

	/**
	 * @return array
	 */
	public function getPackageConfigNames()
	{
		return $this->_packageConfig;
	}

	/**
	 * @return array
	 */
	public function getLxPackageConfigNames()
	{
		return $this->_lxPackageConfig;
	}

	/**
	 * @param string|null $extension
	 * @return File
	 */
	public function getTempFile($extension = null)
	{
        $tempPath = $this->getSystemPath('temp')
            . '/'
            . Math::randHash()
            . ($extension ? ('.' . $extension) : '');
        return new File($tempPath);
	}
}
