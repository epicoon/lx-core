<?php

namespace lx;

class RenderServiceResponse extends ServiceResponse {
	private $module;

	/**
	 *
	 * */
	public function __construct($module) {
		$this->module = $module;
	}

	/**
	 * //todo не нравится, что собирается вся страница целиком - с js-ядром... может это и нормально. В общей картине будет видно
	 * */
	public function send() {
		if (!$this->module) {
			require_once(\lx::$conductor->stdResponses . '/404.php');
			return;
		}

		// Помечаем модуль как собирающийся при загрузке страницы
		$this->module->setMain(true);
		$builder = new ModuleBuilder($this->module);
		// Если модуль уже скомпилирован - возвращаем статику
		if ($builder->isCompiled()) {
			require_once($builder->compiledFilePath());
			return;
		}

		// JS-ядро
		$core = ClassHelper::call(\lx::class, 'compileJsCore');

		// Глобальный js-код, выполняемый до разворачивания корневого модуля
		$jsBootstrap = ClassHelper::call(\lx::class, 'compileJsBootstrap');
		// Глобальный js-код, выполняемый после разворачивания корневого модуля
		$jsMain = ClassHelper::call(\lx::class, 'compileJsMain');

		// Попытка построить модуль
		$buildResult = $builder->getResult();
		if ($buildResult === false) {
			$error = $builder->getError();
			require_once(\lx::$conductor->stdResponses . '/400.php');
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

		$lxCss = '<link href="' . explode(\lx::sitePath(), \lx::$conductor->core)[1] . '/css/lx.css" type="text/css" rel="stylesheet">';
		require_once(\lx::$conductor->stdResponses . '/200.php');
	}
}
