<?php

namespace lx;

use lx;

class JsLinkDocumentation
{
    private string $module;
    private string $class;
    private string $method;
    private string $param;
    
    public function __construct(array $config)
    {
        $this->module = $config['module'];
        $this->class = $config['class'];
        $this->method = $config['method'];
        $this->param = $config['param'];
    }
    
    public function toArray(): array
    {
        $info = lx::$app->jsModules->getModuleInfo($this->module);
        if (!$info) {
            return [];
        }

        $doc = $info->getDocumentation();
        /** @var JsClassDocumentation|null $classDoc */
        $classDoc = $doc[$this->class] ?? null;
        if (!$classDoc) {
            return [];
        }

        $method = $classDoc->getMethod($this->method);
        if (!$method) {
            return [];
        }

        $param = $method->getParam($this->param);
        if (!$param) {
            return [];
        }

        return $param->toArray();
    }
}
