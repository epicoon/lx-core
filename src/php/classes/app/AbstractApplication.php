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
 * @property-read CssManagerInterface $cssManager
 * @property-read JsModulesComponent $jsModules
 * @property-read JsModuleInjectorInterface $moduleInjector
 * @property-read LoggerInterface $logger
 */
abstract class AbstractApplication implements FusionInterface
{
    const EVENT_BEFORE_RUN = 'beforeApplicationRun';
    const EVENT_AFTER_RUN = 'afterApplicationRun';

    private int $pid;
    private string $_sitePath;
    private array $_config;
    private array $_params;
    private ?array $defaultServiceConfig = null;
    private ?array $defaultPluginConfig = null;
    private ApplicationComponents $components;
    private ApplicationConductor $_conductor;
    private ServicesMap $_services;
    private EventManagerInterface $_events;
    private DependencyProcessor $_diProcessor;
    private bool $logMode;

    protected array $settings = [];

    public function __construct(iterable $config = [])
    {
        $this->pid = getmypid();
        $this->_sitePath = lx::$conductor->sitePath;

        lx::$app = $this;
        $this->components = new ApplicationComponents();
        $this->_conductor = new ApplicationConductor();
        $this->_services = new ServicesMap();
        $this->logMode = true;

        $dependencyProcessor = $config['dependencyProcessor'] ?? [];
        unset($config['dependencyProcessor']);
        $diConfig = ClassHelper::prepareConfig($dependencyProcessor, DependencyProcessor::class);
        $this->_diProcessor = new $diConfig['class']($diConfig['params']);

        $this->_params = [];
        $this->_config = $config;
        $this->loadConfig();

        $this->_events = $this->diProcessor->build()
            ->setInterface(EventManagerInterface::class)
            ->setDefaultClass(EventManager::class)
            ->setContextClass(static::class)
            ->getInstance();

        $this->initFusionComponents($this->getConfig('components') ?? []);
        $this->init();
    }

    protected function init(): void
    {
        // pass
    }

    public function initFusionComponents(array $list): void
    {
        $this->components->initFusionComponents($list, $this);
    }

    public function hasFusionComponent(string $name): bool
    {
        return $this->components->hasFusionComponent($name);
    }

    public function setFusionComponent(string $name, array $config): void
    {
        $this->components->setFusionComponent($name, $config);
    }

    public function getFusionComponent(string $name): ?FusionComponentInterface
    {
        return $this->components->getFusionComponent($name);
    }

    public function eachFusionComponent(callable $callback): void
    {
        $this->components->eachFusionComponent($callback);
    }
    
    public function getFusionComponentTypes(): array
    {
        return [
            'dbConnector' => DbConnectorInterface::class,
            'router' => RouterInterface::class,
            'userManager' => UserManagerInterface::class,
            'user' => UserInterface::class,
            'authenticationGate' => AuthenticationInterface::class,
            'authorizationGate' => AuthorizationInterface::class,
            'corsProcessor' => CorsProcessor::class,
            'language' => Language::class,
            'i18nMap' => ApplicationI18nMap::class,
            'cssManager' => CssManagerInterface::class,
            'moduleInjector' => JsModuleInjectorInterface::class,
            'logger' => LoggerInterface::class,
        ];
    }

    public function getDefaultFusionComponents(): array
    {
        return [
            'language' => Language::class,
            'i18nMap' => ApplicationI18nMap::class,
            'cssManager' => CssManager::class,
            'jsModules' => JsModulesComponent::class,
            'moduleInjector' => ApplicationJsModuleInjector::class,
            'logger' => ApplicationLogger::class,
        ];
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

		return $this->components->__get($name);
	}

	public function getPid(): int
    {
        return $this->pid;
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
		return $this->getConfig('mode') ?? lx::MODE_PROD;
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
		if (!array_key_exists($name, $map->services)) {
			return null;
		}

		$path = $map->services[$name];
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

    public function getBuildData(): array
    {
        $components = [];
        $this->eachFusionComponent(function($component, $name) use (&$components) {
            if ($component instanceof ClientComponentInterface) {
                $data = $component->getCLientData();
                if (!empty($data)) {
                    $components[$name] = $data;
                }
            }
        });

        return [
            'settings' => $this->getSettings(),
            'components' => $components,
        ];
    }

    public function applyBuildData(array $data): void
    {
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @return mixed
     */
    public function getSetting(string $name)
    {
        $settings = $this->getSettings();
        return $settings[$name] ?? null;
    }

    /**
     * @param mixed $value
     */
    public function addSetting(string $name, $value)
    {
        $this->settings[$name] = $value;
    }

    /**
     * @param array|string $config
     */
    public function useI18n($config): void
    {
        $map = [];
        if (is_array($config)) {
            if (isset($config['service'])) {
                if ($this->i18nMap->inUse($config['service'])) {
                    return;
                } else {
                    $this->i18nMap->noteUse($config['service']);
                }

                $map = $this->getService($config['service'])->i18nMap->getMap();
            } elseif (isset($config['plugin'])) {
                if ($this->i18nMap->inUse($config['plugin'])) {
                    return;
                } else {
                    $this->i18nMap->noteUse($config['plugin']);
                }

                $map = $this->getPlugin($config['plugin'])->i18nMap->getMap();
            }
        } elseif (is_string($config)) {
            $path = $this->conductor->getFullPath($config);
            if ($this->i18nMap->inUse($path)) {
                return;
            }

            $file = $this->diProcessor->createByInterface(DataFileInterface::class, [$path]);
            if ($file->exists()) {
                $this->i18nMap->noteUse($path);
                $data = $file->get();
                if (is_array($data)) {
                    $map = $data;
                }
            }
        }

        if (!empty($map)) {
            $this->i18nMap->add($map, true);
        }
    }

	abstract public function run(): void;

	private function loadConfig(): void
	{
		$path = lx::$conductor->getAppConfig($this);
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
        $this->_conductor->setAliases($this->getConfig('aliases') ?? []);

        $this->loadLocalConfig();
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
        $this->_conductor->setAliases($this->getConfig('aliases') ?? []);

        // Validation creates dev-log messages if classes don't due to interfaces.
        // This collisions must be solved during development.
        if ($this->isNotProd()) {
            $this->diProcessor->validate();
        }
    }
}
