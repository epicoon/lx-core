<?php

namespace lx;

/**
 * Class Application
 * @package lx
 *
 * @property Dialog $dialog
 * @property Router $router
 *
 * @property-read Language $language
 * @property-read I18nApplicationMap $i18nMap
 * @property-read User $user
 * @property-read EventManager $events
 * @property-read DependencyProcessor $diProcessor
 */
class Application extends AbstractApplication implements FusionInterface
{
	use FusionTrait;

	/** @var Dialog */
	private $_dialog;

	/** @var Router */
	private $_router;

	/** @var array */
	private $settings;

	/**
	 * Application constructor.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->settings = [
			'unpackType' => \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
		];

		$this->_dialog = new Dialog();
		$this->retrieveRouter();

		$this->initFusionComponents($this->getConfig('components'), [
			'language' => Language::class,
			'i18nMap' => I18nApplicationMap::class,
			'user' => User::class,
			'events' => EventManager::class,
			'diProcessor' => DependencyProcessor::class,
		]);
	}

	/**
	 * @param string $name
	 * @return Dialog|Router|mixed
	 */
	public function __get($name)
	{
		switch ($name) {
			case 'dialog':
				return $this->_dialog;
			case 'router':
				return $this->_router;
		}

		return parent::__get($name);
	}

	/**
	 * @return array
	 */
	public function getBuildData()
	{
		return [
			'settings' => $this->settings,
		];
	}

	/**
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * @param string $name
	 * @return mixed|null
	 */
	public function getSetting($name)
	{
		if (array_key_exists($name, $this->settings))
			return $this->settings[$name];
		return null;
	}

	/**
	 * @param string $name
	 * @param mixed $value
	 */
	public function addSetting($name, $value)
	{
		$this->settings[$name] = $value;
	}

	/**
	 * @param array|string $config
	 */
	public function useI18n($config)
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

	/**
	 * @param array $data
	 */
	public function applyBuildData($data)
	{
	}

	public function run()
	{
		if ($this->lifeCycle) {
			$this->lifeCycle->beforeRun();
		}

		$this->authenticateUser();

		$requestHandler = new RequestHandler();
		$requestHandler->run();
		$requestHandler->send();

		if ($this->lifeCycle) {
			$this->lifeCycle->afterRun();
		}
	}

	/**
	 * @return array
	 */
	public function getCommonJs()
	{
		$compiler = new JsCompiler();

		$jsBootstrap = $this->compileJsBootstrap($compiler);
		$jsMain = $this->compileJsMain($compiler);
		$jsBootstrap = addcslashes($jsBootstrap, '\\');
		$jsMain = addcslashes($jsMain, '\\');

		return [$jsBootstrap, $jsMain];
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Method defines application router
	 */
	private function retrieveRouter()
	{
		$router = null;
		$routerData = $this->getConfig('router');
		if ($routerData && isset($routerData['type'])) {
			switch ($routerData['type']) {
				case 'map':
					$data = null;
					if (isset($routerData['path'])) {
						$path = $this->conductor->getFullPath($routerData['path']);
						$file = $this->diProcessor->createByInterface(DataFileInterface::class, [$path]);
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

	/**
	 * Method defines current user
	 */
	private function authenticateUser()
	{
		if ($this->user && $this->authenticationGate) {
			$this->authenticationGate->authenticateUser();
		}
	}

	/**
	 * Global JS-code executed before plugin rise
	 *
	 * @param JsCompiler $compiler
	 * @return string
	 */
	private function compileJsBootstrap($compiler)
	{
		$path = $this->getConfig('jsBootstrap');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}

	/**
	 * Global JS-code executed after plugin rise
	 *
	 * @param JsCompiler $compiler
	 * @return string
	 */
	private function compileJsMain($compiler)
	{
		$path = $this->getConfig('jsMain');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}
}
