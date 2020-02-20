<?php

namespace lx;

/**
 * Class Application
 * @package lx
 *
 * @property string $sitePath
 * @property Autoloader $autoloader
 * @property Dialog $dialog
 * @property ApplicationConductor $conductor
 * @property Router $router
 * @property I18nApplicationMap $i18nMap
 *
 * @property Language $language
 * @property User $user
 * @property EventManager $events
 * @property DependencyProcessor $diProcessor
 */
class Application extends AbstractApplication implements FusionInterface {
	use FusionTrait;

	private $_dialog;
	private $_router;
	private $_i18nMap;
	
	/**
	 * Данные, которые будут отправлены как клиентские настройки lx
	 * */
	private $settings;

	public function __construct() {
		parent::__construct();
		$this->settings = [
			'unpackType' => \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
		];

		$this->_dialog = new Dialog();
		$this->retrieveRouter();

		$this->initFusionComponents($this->getConfig('components'), [
			'language' => Language::class,
			'user' => User::class,
			'events' => EventManager::class,
			'diProcessor' => DependencyProcessor::class,
		]);
	}

	public function __get($name) {
		switch ($name) {
			case 'dialog': return $this->_dialog;
			case 'router': return $this->_router;
			case 'i18nMap': {
				if (!$this->_i18nMap) {
					$this->_i18nMap = new I18nApplicationMap();
				}

				return $this->_i18nMap;
			}
		}

		$component = $this->getFusionComponent($name);
		if ($component) {
			return $component;
		}

		return parent::__get($name);
	}

	public function getBuildData() {
		return [
			'settings' => $this->settings,
		];
	}

	/**
	 * Получение всех настроек
	 * */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Получение конкретной настройки
	 * */
	public function getSetting($name) {
		if (array_key_exists($name, $this->settings))
			return $this->settings[$name];
		return null;
	}

	/**
	 * Добавить поле настроек, которое отправится в клиентский lx
	 * */
	public function addSetting($name, $value) {
		$this->settings[$name] = $value;
	}

	public function useI18n($config) {
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
			
			$file = new ConfigFile($path);
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

	public function applyBuildData($data) {
	}

	public function run() {
		ob_start();
		
		$this->determineUser();
		
		$requestHandler = new RequestHandler();
		$requestHandler->run();
		$requestHandler->send();
	}

	public function getCommonJs() {
		$compiler = new JsCompiler();

		//todo - добавить кэширование
		$jsCore = $this->compileJsCore($compiler);

		//todo - локализация
		// Глобальный js-код, выполняемый до разворачивания корневого плагина
		$jsBootstrap = $this->compileJsBootstrap($compiler);
		// Глобальный js-код, выполняемый после разворачивания корневого плагина
		$jsMain = $this->compileJsMain($compiler);
		//TODO - так и не понял, почему слэши пропадают - где-то в операциях с json
		$jsBootstrap = addcslashes($jsBootstrap, '\\');
		$jsMain = addcslashes($jsMain, '\\');

		return [$jsCore, $jsBootstrap, $jsMain];
	}

	private function retrieveRouter() {
		$router = null;
		$routerData = $this->getConfig('router');
		if ($routerData && isset($routerData['type'])) {
			switch ($routerData['type']) {
				case 'map':
					$data = null;
					if (isset($routerData['path'])) {
						$path = $this->conductor->getFullPath($routerData['path']);
						$file = new ConfigFile($path);
						if ($file->exists()) {
							$data = $file->get();
						}
					} elseif (isset($routerData['routes'])) {
						$data = $routerData['routes'];
					}
					if ($data) {
						$router = new Router();
						$router->setMap($data);
					}
					break;
				case 'class':
					if (isset($routerData['name']) && ClassHelper::exists($routerData['name'])) {
						$router = new $routerData['name']();
					}
					break;
			}
		}

		$this->_router = $router;
	}
	
	private function determineUser()
	{
		if ($this->user && $this->authenticationGate) {
			$this->authenticationGate->authenticateUser();
		}
	}

	/**
	 * Собирает js-ядро
	 * */
	private function compileJsCore($compiler) {
		//TODO - кэшировать это
		$path = $this->conductor->getSystemPath('jsCore');
		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);

		//TODO - кэшировать это
		$servicesList = PackageBrowser::getServicesList();
		$coreExtension = '';
		foreach ($servicesList as $service) {
			$coreExtension .= $service->getJsCoreExtension();
		}
		$code .= $coreExtension;

		$code .= 'lx.lang=' . ArrayHelper::arrayToJsCode($this->language->getCurrentData()) . ';';
		return $code;
	}

	/**
	 * Глобальный js-код, выполняемый до разворачивания корневого плагина
	 * */
	private function compileJsBootstrap($compiler) {
		$path = $this->getConfig('jsBootstrap');
		if (!$path) return '';

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) return '';

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		return $code;
	}

	/**
	 * Глобальный js-код, выполняемый после разворачивания корневого плагина
	 * */
	private function compileJsMain($compiler) {
		$path = $this->getConfig('jsMain');
		if (!$path) return '';

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) return '';

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		return $code;
	}
}
