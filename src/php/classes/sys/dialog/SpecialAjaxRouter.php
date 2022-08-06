<?php

namespace lx;

use lx;

class SpecialAjaxRouter
{
	/**
	 * Method defines if request is special AJAX
	 */
	public static function checkRequest(): bool
	{
		$request = lx::$app->request;
		return (
			$request->isAjax() && $request->getHeader('lx-type')
		);
	}

	public function route(): ?ResourceContext
	{
		switch (lx::$app->request->getHeader('lx-type')) {
			case 'service': return $this->serviceAjaxResponse();
			case 'plugin': return $this->pluginAjaxResponse();
			case 'module': return $this->moduleAjaxResponse();
		}
		return null;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function serviceAjaxResponse(): ResourceContext
	{
		$type = lx::$app->request->getHeader('lx-service');

		// AJAX-request for required modules
		if ($type == 'get-modules') {
			$data = lx::$app->request->getParams();
			return new ResourceContext([
				'class' => JsModuleProvider::class,
				'method' => 'getModulesResponse',
				'params' => [$data],
			]);
		}
	}

	private function pluginAjaxResponse(): ?ResourceContext
	{
		$meta = lx::$app->request->getHeader('lx-plugin');
		if ($meta === null) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => 'Plugin-ajax-request without plugin!',
			]);
			return null;
		}

		$arr = explode(' ', $meta);
		$pluginName = $arr[0];
		$plugin = lx::$app->getPlugin($pluginName);
		if ($plugin === null) {
			lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
				'__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
				'msg' => "Plugin '$pluginName' not found",
			]);
			return null;
		}

		$respondentName = $arr[1] ?? null;
        if (!$respondentName) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Respondent '{$respondentName}' not found",
            ]);
            return null;
        }

        $data = lx::$app->request->getParams();
        if (!isset($data['attributes']) || !isset($data['data'])) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Wrong data in ajax-request for plugin '{$plugin->name}'",
            ]);
            return null;
        }

        $plugin->attributes->setProperties($data['attributes']);
        $requestData = $data['data'];
        $respInfo = preg_split('/[^\w\d_]/', $respondentName);
        $respondent = $plugin->getRespondent($respInfo[0] ?? '');
        if (!$respondent) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Respondent '$respondentName' is not found",
            ]);
            return null;
        }

        $methodName = $respInfo[1] ?? '';
        if (!method_exists($respondent, $methodName)) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Method '$methodName' for respondent '$respondentName' is not found",
            ]);
            return null;
        }

        return new ResourceContext([
            'object' => $respondent,
            'method' => $methodName,
            'params' => $requestData,
        ]);
	}

	private function moduleAjaxResponse(): ?ResourceContext
	{
        list($moduleName, $methodName) = explode(':', lx::$app->request->getHeader('lx-module'));
        if (!$moduleName) {
            return null;
        }
        
		$serverModuleName = lx::$app->jsModules->getModuleInfo($moduleName)->getMetaData('backend');
		if (!$serverModuleName || !ClassHelper::exists($serverModuleName)) {
			return null;
		}

		$ref = new \ReflectionClass($serverModuleName);
		if (!$ref->isSubclassOf(Module::class)) {
			return null;
		}

		return new ResourceContext([
			'class' => $serverModuleName,
			'method' => $methodName,
			'params' => lx::$app->request->getParams(),
		]);
	}
}
