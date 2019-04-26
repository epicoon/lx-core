<?php

namespace lx;

/**
 * Класс для определения ресурса, запрошенного ajax-запросом
 * */
class AjaxRouter {
	/**
	 * Определить запрошенный ресурс
	 * @return lx\ResponseSource | null
	 * */
	public function route() {
		switch (\lx::$dialog->header('lx-type')) {
			// Служебный (системный) ajax-запрос
			case 'service': return $this->serviceAjaxResponse();

			// Ajax-запрос, произошедший в контексте какого-то модуля
			case 'module': return $this->moduleAjaxResponse();
			
			// Ajax-запрос, произошедший в контексте какого-то виджета
			case 'widget': return $this->widgetAjaxResponse();
		}

		return null;
	}
	
	/**
	 * Служебные lx-запросы
	 * */
	private function serviceAjaxResponse() {
		$type = \lx::$dialog->header('lx-service');

		// Ajax-запрос на дозагрузку виджетов
		if ($type == 'get-widgets') {
			$data = \lx::$dialog->params();
			return new ResponseSource([
				'isStatic' => true,
				'class' => WidgetHelper::class,
				'method' => 'getWidgetsCode',
				'params' => [$data],
			]);
		}
	}

	/**
	 * Формирование ajax-ответа для модуля
	 * */
	private function moduleAjaxResponse() {
		$moduleName = \lx::$dialog->header('lx-module');
		if ($moduleName === null) {
			//todo логирование? 'Module-ajax-request without module!'
			return false;
		}

		$module = \lx::getModule($moduleName);
		if ($module === null) {
			//todo логирование? "Module '$moduleName' not found"
			return false;
		}

		return ClassHelper::call($module, 'getResponseSource', [\lx::$dialog->params()]);
	}

	/**
	 * Формирование ajax-ответа для виджета
	 * */
	private function widgetAjaxResponse() {
		$widgetName = \lx::$dialog->header('lx-widget');
		$widgetName = str_replace('.', '\\', $widgetName);

		if (!ClassHelper::exists($widgetName) ) {
			return false;
		}

		$ref = new \ReflectionClass($widgetName);
		if (!$ref->isSubclassOf( Rect::class )) {
			return false;
		}

		$data = \lx::$dialog->params();
		$methodKey = $data['key'];
		$params = $data['params'];

		return new ResponseSource([
			'isStatic' => true,
			'class' => $widgetName,
			'method' => 'ajax',
			'params' => [$methodKey, $params],
		]);
	}
}
