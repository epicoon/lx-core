<?php

namespace lx;

class JsModuleProvider extends Source
{
	public function getModulesCode($list) {
		$modulesCode = '';
		foreach ($list as $moduleName) {
			$modulesCode .= '#lx:use ' . $moduleName . ';';
		}
		$compiler = new JsCompiler();
		$compiler->setBuildModules(true);
		$modulesCode = $compiler->compileCode($modulesCode);
		$modulesCode = I18nHelper::localize($modulesCode, $this->app->i18nMap->getMap());

		return $modulesCode;
	}
}
