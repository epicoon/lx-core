<?php

namespace lx;

class JsMethodDocumentation
{
    private bool $isStatic = false;
    private array $doc = [];
    private array $params = [];
    
    public function __construct(array $info)
    {
        foreach ($info as $key => $item) {
            if ($key == 'static') {
                $this->isStatic = $item;
                continue;
            }

            if ($key == 'params') {
                foreach ($item as $paramName => $paramInfo) {
                    $this->params[$paramName] = new JsParamDocumentation($paramName, $paramInfo);
                }
                
                $this->params = $item;
                continue;
            }
            
            $this->doc[$key] = $item;
        }
    }

    public function toArray(): array
    {
        return array_merge($this->doc, [
            'params' => $this->params,
        ]);
    }
}
