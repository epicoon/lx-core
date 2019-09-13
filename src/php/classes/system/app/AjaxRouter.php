<?php

namespace lx;

/**
 * Класс для определения ресурса, запрошенного ajax-запросом
 * */
class AjaxRouter extends ApplicationTool {
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
			return new ResponseSource($this->app, [
				'isStatic' => true,
				'class' => ModuleHelper::class,
				'method' => 'getModulesCode',
				'params' => [$this->app, $data],
			]);
		}
	}

	/**
	 * Формирование ajax-ответа для плагина
	 * */
	private function pluginAjaxResponse() {
		$pluginName = $this->app->dialog->header('lx-plugin');
		if ($pluginName === null) {
			//todo логирование? 'Plugin-ajax-request without plugin!'
			return false;
		}

		$plugin = $this->app->getPlugin($pluginName);
		if ($plugin === null) {
			//todo логирование? "Plugin '$pluginName' not found"
			return false;
		}

		return ClassHelper::call($plugin, 'getResponseSource', [$this->app->dialog->params()]);
	}

	/**
	 * Формирование ajax-ответа для виджета
	 * */
	private function widgetAjaxResponse() {
		$widgetName = $this->app->dialog->header('lx-widget');
		$widgetName = str_replace('.', '\\', $widgetName);

		if (!ClassHelper::exists($widgetName) ) {
			return false;
		}

		$ref = new \ReflectionClass($widgetName);
		if (!$ref->isSubclassOf( Rect::class )) {
			return false;
		}

		$data = $this->app->dialog->params();
		$methodKey = $data['key'];
		$params = $data['params'];

		$methodName = $widgetName::ajaxRoute($methodKey);

		return new ResponseSource($this->app, [
			'isWidget' => true,
			'class' => $widgetName,
			'method' => $methodName,
			'params' => $params,
		]);
	}
}
