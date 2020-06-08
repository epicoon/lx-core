<?php

namespace lx;

/**
 * Class SpecialAjaxRouter
 * @package lx
 */
class SpecialAjaxRouter
{
	/**
	 * Method defines if request is special AJAX
	 *
	 * @return bool
	 */
	public static function checkDialog()
	{
		$dialog = \lx::$app->dialog;
		return (
			$dialog->isAjax() && $dialog->getHeader('lx-type')
		);
	}

	/**
	 * @return SourceContext|false
	 */
	public function route()
	{
		switch (\lx::$app->dialog->getHeader('lx-type')) {
			case 'service': return $this->serviceAjaxResponse();
			case 'plugin': return $this->pluginAjaxResponse();
			case 'widget': return $this->widgetAjaxResponse();
		}
		return false;
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @return SourceContext
	 */
	private function serviceAjaxResponse()
	{
		$type = \lx::$app->dialog->getHeader('lx-service');

		// AJAX-request for required modules
		if ($type == 'get-modules') {
			$data = \lx::$app->dialog->getParams();
			return new SourceContext([
				'class' => JsModuleProvider::class,
				'method' => 'getModulesRequest',
				'params' => [$data],
			]);
		}
	}

	/**
	 * @return SourceContext|false
	 */
	private function pluginAjaxResponse()
	{
		$meta = \lx::$app->dialog->getHeader('lx-plugin');
		if ($meta === null) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => 'Plugin-ajax-request without plugin!',
			]);
			return false;
		}

		$arr = explode(' ', $meta);
		$pluginName = $arr[0];
		$plugin = \lx::$app->getPlugin($pluginName);
		if ($plugin === null) {
			\lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Plugin '$pluginName' not found",
			]);
			return false;
		}

		$respondentName = $arr[1] ?? null;
		return $plugin->getSourceContext($respondentName, \lx::$app->dialog->getParams());
	}

	/**
	 * @return SourceContext|false
	 */
	private function widgetAjaxResponse()
	{
		$meta = \lx::$app->dialog->getHeader('lx-widget');

		$arr = explode(':', $meta);
		$moduleName = $arr[0];
		$info = (new JsModuleMap())->getModuleInfo($moduleName);
		$widgetName = $info['data']['backend'] ?? '';

		if (!ClassHelper::exists($widgetName)) {
			return false;
		}

		$ref = new \ReflectionClass($widgetName);
		if (!$ref->isSubclassOf(Rect::class)) {
			return false;
		}

		$methodName = $arr[1];
		$params = \lx::$app->dialog->getParams();

		return new SourceContext([
			'class' => $widgetName,
			'method' => $methodName,
			'params' => $params,
		]);
	}
}
