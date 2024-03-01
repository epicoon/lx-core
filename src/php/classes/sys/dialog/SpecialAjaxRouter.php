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
            ($request->isAjax() || $request->isCors())
            && in_array($request->getUrl(), [
                '/lx_service',
                '/lx_plugin',
                '/lx_module'
            ])
		);
	}

	public function route(): ?ResourceContext
	{
        switch (lx::$app->request->getUrl()) {
            case '/lx_service': return $this->serviceAjaxResponse();
            case '/lx_plugin': return $this->pluginAjaxResponse();
            case '/lx_module': return $this->moduleAjaxResponse();
        }
		return null;
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function serviceAjaxResponse(): ResourceContext
	{
        $data = lx::$app->request->getParams();
        $action = $data['action'];

        // AJAX-request for required modules
        if ($action == 'get-modules') {
            return new ResourceContext([
                'class' => JsModuleProvider::class,
                'method' => 'getModulesResponse',
                'params' => [$data['params']],
            ]);
        }
	}

	private function pluginAjaxResponse(): ?ResourceContext
	{
        $data = lx::$app->request->getParams();

        $pluginName = $data['plugin'] ?? null;
        $plugin = lx::$app->getPlugin($pluginName);
        if ($plugin === null) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Plugin '$pluginName' not found",
            ]);
            return null;
        }

        $respondentName = $data['respondent'] ?? null;
        if (!$respondentName) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Respondent '{$respondentName}' not found",
            ]);
            return null;
        }

        $pluginAttributes = $data['attributes'] ?? null;
        $requestData = $data['data'] ?? null;
        if ($pluginAttributes === null || $requestData === null) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Wrong data in ajax-request for plugin '{$plugin->name}'",
            ]);
            return null;
        }

        $plugin->attributes->setProperties($pluginAttributes);

        $respInfo = preg_split('/[^\w\d_]/', $respondentName);
        $respondent = $plugin->getRespondent($respInfo[0] ?? '');
        if (!$respondent) {
            lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                'msg' => "Respondent '$respondentName' not found",
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
        $data = lx::$app->request->getParams();
        $moduleName = $data['moduleName'] ?? null;
        if (!$moduleName) {
            return null;
        }

        $methodName = $data['methodName'] ?? null;
        if (!$methodName) {
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
			'params' => $data['params'],
		]);
	}
}
