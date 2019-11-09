<?php

namespace lx;

/**
 * Class Application
 * @package lx
 *
 * @property $sitePath string
 * @property $autoloader Autoloader
 * @property $dialod Dialog
 * @property $conductor Conductor
 * @property $router Router
 */
class Application extends AbstractApplication implements FusionInterface {
	use FusionTrait;

	public $data;

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

		$this->_dialog = new Dialog($this);
		$this->retrieveRouter();

		$this->initFusionComponents($this->getConfig('components'), [
			'language' => Language::class,
			'user' => User::class,
		]);

		$this->data = new DataObject();
	}

	public function __get($name) {
		switch ($name) {
			case 'dialog': return $this->_dialog;
			case 'router': return $this->_router;
			case 'i18nMap': {
				if (!$this->_i18nMap) {
					$this->_i18nMap = new I18nApplicationMap($this);
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
		$this->response = new Response($this);
		$this->response->run();
		$this->response->send();
	}

	public function getCommonJs() {
		$compiler = new JsCompiler($this);

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


		// Глобальные настройки
		$settings = ArrayHelper::arrayToJsCode( $this->getSettings() );
		// Набор глобальных произвольных данных
		$data = ArrayHelper::arrayToJsCode( $this->data->getProperties() );

		$pluginInfo = addcslashes($pluginInfo, '\\');

		// Запуск ядра
		$result .= 'lx.start('
			. $settings . ',' . $data
			. ',`' . $jsBootstrap . '`,`' . $pluginInfo . '`,`' . $jsMain
			. '`);';

		return $result;
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
						$router = new Router($this);
						$router->setMap($data);
					}
					break;
				case 'class':
					if (isset($routerData['name']) && ClassHelper::exists($routerData['name'])) {
						$router = new $routerData['name']($this);
					}
					break;
			}
		}

		$this->_router = $router;
	}

	/**
	 * Собирает js-ядро
	 * */
	private function compileJsCore($compiler) {
		$path = $this->conductor->getSystemPath('jsCore');
		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);

		if ($this->authenticationGate) {
			$code .= $this->authenticationGate->getJs();
		}

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
