<?php

namespace lx;

/**
 * Класс для определения ресурса, запрошенного ajax-запросом
 * */
class AjaxRouter {
	use ApplicationToolTrait;

	/**
	 * Определить запрошенный ресурс
	 * @return lx\ResponseSource | null
	 * */
	public function route() {
		switch ($this->app->dialog->header('lx-type')) {
			// Служебный (системный) ajax-запрос
			case 'service': return $this->serviceAjaxResponse();

			// Ajax-запрос, произошедший в контексте какого-то плагина
			case 'plugin': return $this->pluginAjaxResponse();
			
			// Ajax-запрос, произошедший в контексте какого-то виджета
			case 'widget': return $this->widgetAjaxResponse();
		}

		return false;
	}
	
	/**
	 * Служебные lx-запросы
	 * */
	private function serviceAjaxResponse() {
		$type = $this->app->dialog->header('lx-service');
		
		// Ajax-запрос на дозагрузку виджетов
		if ($type == 'get-modules') {
			$data = $this->app->dialog->params();
			return new ResponseSource([
				'isStatic' => true,
				'class' => ModuleHelper::class,
				'method' => 'getModulesCode',
				'params' => [$data],
			]);
		}
	}

	/**
	 * Формирование ajax-ответа для плагина
	 * */
	private function pluginAjaxResponse() {
		$meta = $this->app->dialog->header('lx-plugin');
		if ($meta === null) {
			//todo логирование? 'Plugin-ajax-request without plugin!'
			return false;
		}

		$arr = explode(' ', $meta);
		$pluginName = $arr[0];
		$plugin = $this->app->getPlugin($pluginName);
		if ($plugin === null) {
			//todo логирование? "Plugin '$pluginName' not found"
			return false;
		}

		$respondentName = $arr[1] ?? null;
		return $plugin->getResponseSource($respondentName, $this->app->dialog->params());
	}

	/**
	 * Формирование ajax-ответа для виджета
	 * */
	private function widgetAjaxResponse() {
		$meta = $this->app->dialog->header('lx-widget');

		$arr = explode(':', $meta);
		$moduleName = $arr[0];
		$info = (new JsModuleMap())->getModuleInfo($moduleName);
		$widgetName = $info['data']['backend'] ?? '';

		if (!ClassHelper::exists($widgetName) ) {
			return false;
		}

		$ref = new \ReflectionClass($widgetName);
		if (!$ref->isSubclassOf( Rect::class )) {
			return false;
		}

		$methodKey = $arr[1];
		$params = $this->app->dialog->params();

		$methodName = $widgetName::ajaxRoute($methodKey);

		return new ResponseSource([
			'isWidget' => true,
			'class' => $widgetName,
			'method' => $methodName,
			'params' => $params,
		]);
	}
}
