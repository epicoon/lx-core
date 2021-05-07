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
	private string $_sitePath;
	private string $_web;
	private string $_webCss;
	private string $_webJs;
	private string $_core;
	private string $_jsCore;
	private string $_jsNode;
	private string $_stdResponses;
	private string $_lxFiles;
	private string $_system;
	private string $_lxData;
	private string $_clientConfig;
	private string $_devLog;
	private string $_defaultServiceConfig;
	private string $_defaultPluginConfig;
	private array $_lxPackageConfig;
	private array $_packageConfig;
	private array $publicFields;

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

	public function __get(string $name): ?string
	{
		if ($name == 'site' || $name == 'sitePath') {
			return $this->_sitePath;
		}

		if (in_array($name, $this->publicFields)) {
			return $this->{'_' . $name};
		}

		return null;
	}

	public function getPath(): string
	{
		return $this->_sitePath;
	}

	public function getSystemPath(?string $name = null): string
	{
		if ($name) {
			return $this->_system . '/' . $name;
		}

		return $this->_system;
	}

	public function getAppConfig(): ?string
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

	public function getDefaultServiceConfig(): string
	{
		return $this->_defaultServiceConfig;
	}

	public function getDefaultPluginConfig(): string
	{
		return $this->_defaultPluginConfig;
	}

	public function getPackageConfigNames(): array
	{
		return $this->_packageConfig;
	}

	public function getLxPackageConfigNames(): array
	{
		return $this->_lxPackageConfig;
	}

	public function getTempFile(?string $extension = null): File
	{
        $tempPath = $this->getSystemPath('temp')
            . '/'
            . Math::randHash()
            . ($extension ? ('.' . $extension) : '');
        return new File($tempPath);
	}
}
