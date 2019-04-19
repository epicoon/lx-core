<?php

namespace lx;

class Response {
	private $source;

	/**
	 *
	 * */
	public function __construct($source) {
		$this->source = $source;
	}

	/**
	 *
	 * */
	public function send() {
		if ($this->source->isModule()) {
			$module = $this->source->getModule();
			$this->renderModuleProcess($module);
		} else {
			$service = $this->source->getService();
			list ($class, $method) = $this->source->getClassAndMethod();
			$instance = new $class($service);
			$result = $instance->$method();

			\lx::$dialog->send($result);
		}
	}
	
	/**
	 * //todo не нравится, что собирается вся страница целиком - с js-ядром... может это и нормально. В общей картине будет видно
	 * */
	public function renderModuleProcess($module) {
		$stdResponses = \lx::$conductor->getSystemPath('stdResponses');

		if (!$module) {
			require_once($stdResponses . '/404.php');
			return;
		}

		// Помечаем модуль как собирающийся при загрузке страницы
		$module->setMain(true);
		$builder = new ModuleBuilder($module);
		// Если модуль уже скомпилирован - возвращаем статику
		if ($builder->isCompiled()) {
			require_once($builder->compiledFilePath());
			return;
		}


		//todo
		\lx::addSetting('lang', \lx::$components->language->getCurrentData());


		// JS-ядро
		$core = ClassHelper::call(\lx::class, 'compileJsCore');

		// Глобальный js-код, выполняемый до разворачивания корневого модуля
		$jsBootstrap = ClassHelper::call(\lx::class, 'compileJsBootstrap');
		// Глобальный js-код, выполняемый после разворачивания корневого модуля
		$jsMain = ClassHelper::call(\lx::class, 'compileJsMain');
		//todo - локализация

		// Попытка построить модуль
		$buildResult = $builder->getResult();
		if ($buildResult === false) {
			$error = $builder->getError();
			require_once($stdResponses . '/400.php');
			return;
		}

		extract($buildResult);
		/**
		 * Всё, что касается модуля, приходят данные:
		 * @var $title
		 * @var $moduleInfo
		 * @var $scripts
		 * @var $css
		 * */
		$headScripts = isset($scripts['headScripts']) ? $scripts['headScripts'] : '';

		// Глобальные настройки
		$settings = ClassHelper::call(\lx::class, 'toJS', [\lx::getSettings()]);
		// Набор глобальных произвольных данных
		$data = ClassHelper::call(\lx::class, 'toJS', [\lx::$data->getProperties()]);


		$relPath = explode(\lx::sitePath(), \lx::$conductor->getSystemPath('core'))[1];
		$lxCss = '<link href="'
			. $relPath
			. '/css/lx.css" type="text/css" rel="stylesheet">';

		//todo
		$icon = $relPath . '/img/icon.png';

		require_once($stdResponses . '/200.php');
	}
}
