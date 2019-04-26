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
		$result = null;

		if ($this->source->isModule()) {
			$module = $this->source->getModule();
			if (\lx::$dialog->isPageLoad()) {
				$this->renderModule($module);
			} else {
				$builder = new ModuleBuilder($module);
				$result = $builder->getResult();
			}
		} else {
			$result = $this->source->invoke();
		}

		if ($result === false) {
			return 400;
		}

		\lx::$dialog->send($result);
		return 200;
	}
	
	/**
	 * 
	 * */
	private function renderModule($module) {
		if (!$module) {
			\lx::renderStandartResponse(404);
			return;
		}

		// Глобальный js-код, выполняемый до разворачивания корневого модуля
		$jsBootstrap = ClassHelper::call(\lx::class, 'compileJsBootstrap');
		// Глобальный js-код, выполняемый после разворачивания корневого модуля
		$jsMain = ClassHelper::call(\lx::class, 'compileJsMain');
		//todo - локализация

		// Помечаем модуль как собирающийся при загрузке страницы
		$module->setMain(true);
		// Попытка построить модуль
		$builder = new ModuleBuilder($module);
		$buildResult = $builder->getResult();
		if ($buildResult === false) {
			$error = $builder->getError();
			\lx::renderStandartResponse(400);
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

		// Запуск ядра
		$js = 'lx.start(' . $settings . ',' . $data . ',`' . $jsBootstrap . '`,`' . $moduleInfo . '`,`' . $jsMain . '`);';

		\lx::renderStandartResponse(200, [
			'title' => $title,
			// 'icon' => $icon,  //todo
			'css' => $css,
			'headScripts' => $headScripts,
			'js' => $js,
		]);
	}
}
