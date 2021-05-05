<?php

namespace lx;

use lx;

/**
 * Class AbstractApplication
 * @package lx
 *
 * @property-read string $sitePath
 * @property-read Directory $directory
 * @property-read ApplicationConductor $conductor
 * @property-read ServicesMap $services
 * @property-read DependencyProcessor $diProcessor
 * @property-read EventManager $events
 * @property-read LoggerInterface $logger
 */
abstract class AbstractApplication implements FusionInterface
{
    use ObjectTrait;
    use FusionTrait;

	private string $id;
	private int $pid;
	private string $_sitePath;
	private array $_config;
	private ?array $defaultServiceConfig = null;
	private ?array $defaultPluginConfig = null;
	private ApplicationConductor $_conductor;
	private ServicesMap $_services;
	private EventManager $_events;
	private DependencyProcessor $_diProcessor;
	private bool $logMode;

	public function __construct(?array $config = [])
	{
	    if ($config === null) {
	        return;
        }

        $this->baseInit($config);
        $this->advancedInit();
	}

	public static function firstConstruct(array $config = []): AbstractApplication
    {
        $app = new static(null);
        $app->baseInit($config);
        (new AutoloadMapBuilder())->createCommonAutoloadMap();
        lx::$autoloader->map->reset();
        $app->advancedInit();
        (new JsModuleMapBuilder())->renewHead();
        return $app;
    }

    protected function baseInit(array $config = []): void
    {
        $this->id = Math::randHash();
        $this->pid = getmypid();
        $this->_sitePath = lx::$conductor->sitePath;
        $this->logMode = true;

        $this->_conductor = new ApplicationConductor();
        lx::$app = $this;

        if (empty($config)) {
            $this->renewConfig();
        } elseif ($config === null) {
            $this->_config = [];
        } else {
            $this->_config = $config;
        }

        $this->_services = new ServicesMap();
    }

    protected function advancedInit(): void
    {
        $diConfig = ClassHelper::prepareConfig(
            $this->getConfig('diProcessor'),
            DependencyProcessor::class
        );
        $this->_diProcessor = new $diConfig['class']($diConfig['params']);

        $eventsConfig = ClassHelper::prepareConfig(
            $this->getConfig('eventManager'),
            EventManager::class
        );
        $this->_events = new $eventsConfig['class']($eventsConfig['params']);

        $this->loadLocalConfig();

        $aliases = $this->getConfig('aliases');
        if (!$aliases) $aliases = [];
        $this->_conductor->setAliases($aliases);

        $this->initFusionComponents($this->getConfig('components'), static::getDefaultComponents());
        $this->init();
    }

    protected static function getDefaultComponents(): array
    {
        return [
            'logger' => ApplicationLogger::class,
        ];
    }

    protected function init(): void
    {
        // pass
    }

	/**
	 * @param $name string
	 * @return mixed
	 */
	public function __get(string $name)
	{
		switch ($name) {
			case 'sitePath':
				return $this->_sitePath;
			case 'directory':
				return new Directory($this->_sitePath);
			case 'conductor':
				return $this->_conductor;
			case 'services':
				return $this->_services;
            case 'diProcessor':
                return $this->_diProcessor;
            case 'events':
                return $this->_events;
		}

		return $this->__objectGet($name);
	}

	public function getPid(): int
    {
        return $this->pid;
    }

	public function getFullPath(string $alias): string
	{
		return $this->conductor->getFullPath($alias);
	}

	public function setLogMode(bool $value)
    {
        $this->logMode = $value;
    }

	/**
	 * @param string|array $data
	 * @param string $locationInfo
	 */
	public function log($data, ?string $locationInfo = null)
	{
	    if ($this->logMode) {
            $this->logger->log($data, $locationInfo);
        }
	}

	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @param string|null $param
	 * @return mixed
	 */
	public function getConfig(?string $param = null)
	{
		if ($param === null) {
			return $this->_config;
		}

		if (array_key_exists($param, $this->_config)) {
			return $this->_config[$param];
		}

		return null;
	}

	public function getDefaultServiceConfig(): array
	{
		if ($this->defaultServiceConfig === null) {
			$this->defaultServiceConfig =
				(new File(lx::$conductor->getDefaultServiceConfig()))->load();
		}

		return $this->defaultServiceConfig;
	}

	public function getDefaultPluginConfig(): array
	{
		if ($this->defaultPluginConfig === null) {
			$this->defaultPluginConfig =
				(new File(lx::$conductor->getDefaultPluginConfig()))->load();
		}

		return $this->defaultPluginConfig;
	}

	public function getMode(): string
	{
		return $this->getConfig('mode');
	}

	/**
	 * @param string|array $mode
	 * @return bool
	 */
	public function isMode($mode): bool
	{
		$currentMode = $this->getMode();
		if (!$currentMode) return true;

		if (is_array($mode)) {
			foreach ($mode as $value) {
				if ($value == $currentMode) {
					return true;
				}
			}
			return false;
		}

		return $mode == $currentMode;
	}

	public function isProd(): bool
	{
		return $this->isMode(lx::MODE_PROD);
	}

	public function isNotProd(): bool
	{
		return !$this->isMode(lx::MODE_PROD);
	}

	public function getService(string $name): ?Service
	{
		if (Service::exists($name)) {
			return $this->services->get($name);
		}

		return null;
	}

	/**
	 * @param string|File $file
	 * @return Service|null
	 */
	public function getServiceByFile($file): ?Service
	{
		if (is_string($file)) {
			$filePath = $this->conductor->getFullPath($file);
		} elseif ($file instanceof File) {
			$filePath = $file->getPath();
		} else {
			return null;
		}

		$map = Autoloader::getInstance()->map->packages;
		foreach ($map as $name => $servicePath) {
			$fullServicePath = addcslashes($this->sitePath . '/' . $servicePath, '/');
			if (preg_match('/^' . $fullServicePath . '\//', $filePath)) {
				return $this->getService($name);
			}
		}

		return null;
	}

	public function getPackagePath(string $name): ?string
	{
		$map = Autoloader::getInstance()->map;
		if (!array_key_exists($name, $map->packages)) {
			return null;
		}

		$path = $map->packages[$name];
		return $this->conductor->getFullPath($path);
	}

	public function getPlugin(string $fullPluginName, array $attributes = [], string $onLoad = ''): ?Plugin
	{
		if (is_array($fullPluginName)) {
			return $this->getPlugin(
				$fullPluginName['name'] ?? $fullPluginName['plugin'] ?? '',
				$fullPluginName['attributes'] ?? [],
				$fullPluginName['onLoad'] ?? ''
			);
		}

		$arr = explode(':', $fullPluginName);
		if (count($arr) != 2) return null;
		$serviceName = $arr[0];
		$pluginName = $arr[1];

		$service = $this->getService($serviceName);
		if (!$service) return null;

		$plugin = $service->getPlugin($pluginName);
		if (!empty($attributes)) {
			$plugin->addAttributes($attributes);
		}

		if ($onLoad != '') {
			$plugin->onLoad($onLoad);
		}
		return $plugin;
	}

	public function getPluginPath(string $serviceName, ?string $pluginName = null): ?string
	{
		if (!$serviceName) {
			return null;
		}

		if ($pluginName === null) {
			$arr = explode(':', $serviceName);
			$serviceName = $arr[0];
			$pluginName = $arr[1];
		}

		return $this->getService($serviceName)->conductor->getPluginPath($pluginName);
	}

	abstract public function run(): void;

	private function renewConfig(): void
	{
		$path = lx::$conductor->getAppConfig();
		if (!$path) {
			$this->_config = [];
		} else {
			$this->_config = require($path);
		}
	}

	private function loadLocalConfig(): void
    {
        if (!array_key_exists('localConfig', $this->_config)) {
            return;
        }

        $localConfig = $this->_config['localConfig'];
        /** @var DataFileInterface $file */
        $file = $this->diProcessor->createByInterface(DataFileInterface::class, [$localConfig]);
        if (!$file->exists()) {
            return;
        }

        $config = $file->get();
        if (!is_array($config)) {
            return;
        }

        $this->_config = ArrayHelper::mergeRecursiveDistinct($this->_config, $config, true);
    }
}
