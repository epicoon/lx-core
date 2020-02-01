<?php

namespace lx;

class ModuleHelper {
	public static function getModulesCode($list) {
		$modulesCode = '';
		foreach ($list as $moduleName) {
			$modulesCode .= '#lx:use ' . $moduleName . ';';
		}
		$compiler = new JsCompiler();
		$compiler->setBuildModules(true);
		$modulesCode = $compiler->compileCode($modulesCode);
		$modulesCode = I18nHelper::localize($modulesCode, \lx::$app->i18nMap->getMap());
		
		return $modulesCode;
	}
}
