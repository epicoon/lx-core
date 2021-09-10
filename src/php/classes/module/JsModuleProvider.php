<?php

namespace lx;

class JsModuleProvider extends Resource
{
	public function getModulesCode(array $list, array $except = []): string
	{
		$modulesCode = '';
		foreach ($list as $moduleName) {
			$modulesCode .= '#lx:use ' . $moduleName . ';';
		}
		$compiler = new JsCompiler();
		$compiler->setBuildModules(true);
		$compiler->ignoreModules($except);
		$modulesCode = $compiler->compileCode($modulesCode);
		$modulesCode = I18nHelper::localize($modulesCode, $this->app->i18nMap);

		return $modulesCode;
	}

	public function getModulesRequest(array $list): ResponseInterface
    {
        $code = $this->getModulesCode($list['need'], $list['have']);
        return \lx::$app->diProcessor->createByInterface(ResponseInterface::class, [$code]);
    }
}
