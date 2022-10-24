<?php

namespace lx;

use lx;

class AppAssetCompiler
{
    public function compileAppCss(): void
    {
        $compiler = new MainCssCompiler();
        $compiler->compile();
    }

    public static function getCommonCss(array $cssList, ?string $context = null): string
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
                        'msg' => 'Css compiling mismatch' . ($context ? " for {$context}" : '')
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

    public static function getAppPresetedFile(): FileInterface
    {
        return new File(lx::$conductor->webLx . '/preseted.json');
    }

    public function compileJsCore(): void
    {
        $path = lx::$conductor->jsClientCore;
        $code = file_get_contents($path);
        $compiler = new JsCompiler();
        $compiler->setBuildModules(false);
        $code = $compiler->compileCode($code, $path);

        $modules = lx::$app->jsModules->getCoreModules();
        if (!empty($modules)) {
            $code .= (new JsModuleProvider())->getModulesCode($modules);
        }

        $file = new File(lx::$conductor->webLx . '/core.js');
        $file->put($code);
    }
}
