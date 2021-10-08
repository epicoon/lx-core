<?php

namespace lx;

use lx;

/**
 * @property-read string $sitePath
 * @property-read Directory $directory
 * @property-read ApplicationConductor $conductor
 * @property-read ServicesMap $services
 * @property-read ServiceProvider $serviceProvider
 * @property-read PluginProvider $pluginProvider
 * @property-read DependencyProcessor $diProcessor
 * @property-read EventManagerInterface $events
 * 
 * Components:
 * @property-read DbConnectorInterface|null $dbConnector
 * @property-read UserManagerInterface|null $userManager
 * @property-read UserInterface|null $user
 * @property-read AuthenticationInterface|null $authenticationGate
 * @property-read AuthorizationInterface|null $authorizationGate
 * @property-read CorsProcessor|null $corsProcessor
 * @property-read Language|null $language
 * @property-read ApplicationI18nMap|null $i18nMap
 * @property-read LoggerInterface $logger
 */
abstract class AbstractApplication implements FusionInterface
{
    use ObjectTrait;
    use FusionTrait;
    
    const EVENT_BEFORE_RUN = 'beforeApplicationRun';
    const EVENT_AFTER_RUN = 'afterApplicationRun';

    private string $id;
    private int $pid;
    private string $_sitePath;
    private array $_config;
    private array $_params;
    private ?array $defaultServiceConfig = null;
    private ?array $defaultPluginConfig = null;
    private ApplicationConductor $_conductor;
    private ServicesMap $_services;
    private EventManagerInterface $_events;
    private DependencyProcessor $_diProcessor;
    private bool $logMode;

    public function __construct(?array $config = [], bool $useGlobalConfig = true)
    {
        if ($config === null) {
            return;
        }

        $this->baseInit($config, $useGlobalConfig);
        $this->advancedInit($useGlobalConfig);
    }

    public static function firstConstruct(array $config = [], bool $useGlobalConfig = true): AbstractApplication
    {
        $app = new static(null);
        $app->baseInit($config, $useGlobalConfig);
        (new AutoloadMapBuilder())->createCommonAutoloadMap();
        lx::$autoloader->map->reset();
        $app->advancedInit($useGlobalConfig);
        (new JsModuleMapBuilder())->renewHead();
        return $app;
    }

    protected function baseInit(array $config = [], bool $useGlobalConfig = true): void
    {
        $this->id = Math::randHash();
        $this->pid = getmypid();
        $this->_sitePath = lx::$conductor->sitePath;
        $this->logMode = true;

        $this->_conductor = new ApplicationConductor();
        lx::$app = $this;

        $diConfig = ClassHelper::prepareConfig(
            $config['diProcessor'] ?? [],
            DependencyProcessor::class
        );
        $this->_diProcessor = new $diConfig['class']($diConfig['params']);

        $this->_params = [];
        $this->_config = $config;
        if ($useGlobalConfig) {
            $this->renewConfig();
        }

        $this->_services = new ServicesMap();
    }

    protected function advancedInit(bool $useGlobalConfig = true): void
    {
        if ($useGlobalConfig) {
            $this->loadLocalConfig();
        }

        $this->_events = $this->diProcessor->createByInterface(
            EventManagerInterface::class,
            [], [],
            EventManager::class,
            static::class
        );

        $aliases = $this->getConfig('aliases');
        if (!$aliases) $aliases = [];
        $this->_conductor->setAliases($aliases);

        $this->initFusionComponents($this->getConfig('components') ?? []);
        $this->init();
    }

    public function getFusionComponentTypes(): array
    {
        return [
            'dbConnector' => DbConnectorInterface::class,
            'userManager' => UserManagerInterface::class,
            'user' => UserInterface::class,
            'authenticationGate' => AuthenticationInterface::class,
            'authorizationGate' => AuthorizationInterface::class,
            'corsProcessor' => CorsProcessor::class,
            'language' => Language::class,
            'i18nMap' => ApplicationI18nMap::class,
            'logger' => LoggerInterface::class,
        ];
    }

    public function getDefaultFusionComponents(): array
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
            case 'serviceProvider':
                return new ServiceProvider();
            case 'pluginProvider':
                return new PluginProvider();
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

    /**
     * @param mixed $value
     */
	public function setParam(string $name, $value): void
    {
        $this->_params[$name] = $value;
    }

    /**
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->_params[$name] ?? null;
    }

	public function getDefaultServiceConfig(): array
	{
	    return $this->_config['serviceConfig'] ?? [];
	}

	public function getDefaultPluginConfig(): array
	{
        return $this->_config['pluginConfig'] ?? [];
	}

	public function getMode(): string
	{
		return $this->getConfig('mode');
	}

	/**
	 * @param string|array $mode
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

	public function getPackagePath(string $name): ?string
	{
		$map = Autoloader::getInstance()->map;
		if (!array_key_exists($name, $map->packages)) {
			return null;
		}

		$path = $map->packages[$name];
		return $this->conductor->getFullPath($path);
	}

	public function getPlugin(string $pluginName): ?Plugin
	{
	    return $this->pluginProvider->getPluginByName($pluginName);
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
		    return;
        }

        $file = $this->diProcessor->createByInterface(DataFileInterface::class, [$path]);
        if (!$file->exists()) {
            return;
        }

        $config = $file->get();
        if (!is_array($config)) {
            return;
        }

        $this->diProcessor->addMap($config['diProcessor'] ?? [], true);
        $this->_config = ArrayHelper::mergeRecursiveDistinct($this->_config, $config);
	}

	private function loadLocalConfig(): void
    {
        if (!array_key_exists('localConfig', $this->_config)) {
            return;
        }

        $localConfig = $this->_config['localConfig'];
        unset($this->_config['localConfig']);
        /** @var DataFileInterface $file */
        $file = $this->diProcessor->createByInterface(DataFileInterface::class, [$localConfig]);
        if (!$file->exists()) {
            return;
        }

        $config = $file->get();
        if (!is_array($config)) {
            return;
        }

        $this->diProcessor->addMap($config['diProcessor'] ?? [], true);
        $this->_config = ArrayHelper::mergeRecursiveDistinct($this->_config, $config, true);

        // Validation creates dev-log messages if classes don't due to interfaces.
        // This collisions must be solved during development.
        if ($this->isNotProd()) {
            $this->diProcessor->validate();
        }
    }
}
