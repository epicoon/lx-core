<?php

namespace lx;

use lx;

class JsModuleProvider extends Resource
{
	public function getModulesCode(array $list, array $except = []): string
	{
        list ($modulesCode, $pass) = $this->compile($list, $except);
		return $modulesCode;
	}

	public function getModulesResponse(array $list): HttpResponseInterface
    {
        list ($modulesCode, $modules) = $this->compile($list['need'], $list['have']);
        return lx::$app->diProcessor->createByInterface(HttpResponseInterface::class, [
            [
                'data' => [
                    'code' => $modulesCode,
                    'compiledModules' => $modules,
                    'css' => lx::$app->jsModules->getModulesCss($modules),
                ],
            ]
        ]);
    }

    public function compile(array $list, array $except = []): array
    {
        $modulesCode = '';
        foreach ($list as $moduleName) {
            $modulesCode .= '#lx:use ' . $moduleName . ';';
        }
        $compiler = new JsCompiler();
        $compiler->ignoreModules($except);
        $modulesCode = $compiler->compileCode($modulesCode);
        $modulesCode = I18nHelper::localize($modulesCode, lx::$app->i18nMap);

        return [$modulesCode, $compiler->getCompiledModules()];
    }
}
