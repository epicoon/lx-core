<?php

namespace lx;

class ModuleHelper {
	public static function getModulesCode($app, $list) {
		$modulesCode = '';
		foreach ($list as $moduleName) {
			$modulesCode .= '#lx:use ' . $moduleName . ';';
		}
		$compiler = new JsCompiler(\lx::$app);
		$compiler->setBuildModules(true);
		$modulesCode = $compiler->compileCode($modulesCode);
		$modulesCode = I18nHelper::localize($modulesCode, $app->i18nMap->getMap());
		
		return $modulesCode;
	}
}
