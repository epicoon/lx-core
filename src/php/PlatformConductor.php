<?php

namespace lx;

/**
 * Conductor classes can find different pathes in the application
 * This is the most common conductor for the platform
 *
 * @property-read string $sitePath
 * @property-read string $web
 * @property-read string $webLx
 * @property-read string $core
 * @property-read string $jsNode
 * @property-read string $jsServerCore
 * @property-read string $jsClientCore
 * @property-read string $lxData
 * @property-read string $stdResponses
 * @property-read string $devLog
 */
class PlatformConductor
{
    private ?array $_appConfigMap = null;

	private string $_sitePath;
	private string $_web;
    private string $_webLx;
	private string $_core;
	private string $_jsNode;
    private string $_jsServerCore;
    private string $_jsClientCore;
	private string $_stdResponses;
	private string $_lxFiles;
	private string $_system;
	private string $_lxData;
	private string $_clientConfig;
	private string $_devLog;
	private string $_defaultServiceConfig;
	private string $_defaultPluginConfig;
	private array $_serviceConfig;
	private array $_packageConfig;
	private array $publicFields;

	public function __construct()
	{
		$this->_sitePath = dirname(__DIR__, 5);
		$this->_web = $this->_sitePath . '/web';
        $this->_webLx = $this->_web . '/lx';

		$this->_core = $this->_sitePath . '/vendor/lx/core/src';
		$this->_jsNode = $this->_core . '/js/nodeStarter.js';
        $this->_jsServerCore = $this->_core . '/js/serverCore.js';
        $this->_jsClientCore = $this->_core . '/js/clientCore.js';
		$this->_stdResponses = $this->_core . '/php/stdResponses';

		$this->_lxFiles = $this->_sitePath . '/lx';
		$this->_system = $this->_lxFiles . '/.system';
		$this->_lxData = $this->_lxFiles . '/data';
		$this->_clientConfig = $this->_lxFiles . '/config';

		$this->_devLog = $this->_system . '/_dev_log';

		$this->_defaultServiceConfig = $this->_clientConfig . '/service.php';
		$this->_defaultPluginConfig = $this->_clientConfig . '/plugin.php';

		$this->_serviceConfig = ['lx-config', 'lx-config/main'];
		$this->_packageConfig = array_merge($this->_serviceConfig, ['composer.json']);
		$this->publicFields = [
			'web',
            'webLx',
			'core',
			'jsNode',
            'jsServerCore',
            'jsClientCore',
			'lxData',
			'stdResponses',
			'devLog',
		];
	}

	public function __get(string $name): ?string
	{
		if ($name == 'sitePath') {
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

	public function getAppConfig(AbstractApplication $app): ?string
	{
        $map = $this->getAppConfigMap();
        $configPath = null;
        foreach ($map as $class => $path) {
            if ($app instanceof $class) {
                $configPath = $path;
                break;
            }
        }

        if (!$configPath || !file_exists($configPath)) {
            return null;
        }

        return $configPath;
	}

	public function getPackageConfigNames(): array
	{
		return $this->_packageConfig;
	}

	public function getServiceConfigNames(): array
	{
		return $this->_serviceConfig;
	}

	public function getTempFile(?string $extension = null): File
	{
        $tempPath = $this->getSystemPath('temp')
            . '/'
            . Math::randHash()
            . ($extension ? ('.' . $extension) : '');
        return new File($tempPath);
	}

    private function getAppConfigMap(): array
    {
        if ($this->_appConfigMap === null) {
            $config = $this->_clientConfig;
            switch (true) {
                case file_exists($config . '/__map__.php'):
                    $mapPath = $config . '/__map__.php';
                    break;
                case file_exists($config . '/__map__.json'):
                    $mapPath = $config . '/__map__.json';
                    break;
                case file_exists($config . '/__map__.yaml'):
                    $mapPath = $config . '/__map__.yaml';
                    break;
                default: $mapPath = null;
            }
            if ($mapPath) {
                $file = new DataFile($mapPath);
                $this->_appConfigMap = $file->get();
            } else {
                $this->_appConfigMap = [];
            }
        }

        return $this->_appConfigMap;
    }
}
