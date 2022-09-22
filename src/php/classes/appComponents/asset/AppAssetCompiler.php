<?php

namespace lx;

use lx;

class AppAssetCompiler
{
    public function compileAppCss(): void
    {
        $renderer = new MainCssRenderer();
        $renderer->render();
    }

    public static function getCommonCss(array $cssList, ?Plugin $context = null): string
    {
        $map = [];
        foreach ($cssList as $type => $code) {
            if ($code == '') {
                continue;
            }

            preg_match_all('/([^}]+?)(?P<therec>{((?>[^{}]+)|(?P>therec))*})/', $code, $matches);
            $map[$type] = [];
            foreach ($matches[1] as $i => $key) {
                $map[$type][$key] = $matches['therec'][$i];
            }
        }
        if (empty($map)) {
            return '';
        }

        $common = [];
        foreach ($map as $type => $list) {
            foreach ($list as $rule => $values) {
                if (array_key_exists($rule, $common) && $common[$rule] != $values) {
                    \lx::devLog(['_'=>[__FILE__,__CLASS__,__METHOD__,__LINE__],
                        '__trace__' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT&DEBUG_BACKTRACE_IGNORE_ARGS),
                        'msg' => 'Css compiling mismatch' . ($context ? " for plugin {$context->name}" : '')
                            . ': compiled - ' . $common[$rule] . ', alternative - ' . $values,
                    ]);
                    continue;
                }

                $common[$rule] = $values;
            }
        }

        $commonCssCode = '';
        foreach ($common as $rule => $values) {
            $commonCssCode .= $rule . $values;
        }
        return $commonCssCode;
    }

    public function compileJsCore(): void
    {
        $path = lx::$conductor->jsClientCore;
        $code = file_get_contents($path);
        $code = (new JsCompiler())->compileCode($code, $path);

        $modules = [];
        lx::$app->eachFusionComponent(function($component, $name) use (&$modules) {
            if ($component instanceof JsModuleClientInterface) {
                $modules = array_merge($modules, $component->getJsModules());
            }
        });
        if (!empty($modules)) {
            $code .= (new JsModuleProvider())->getModulesCode($modules);
        }

        $file = new File(lx::$conductor->webLx . '/core.js');
        $file->put($code);
    }
}
