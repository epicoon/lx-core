<?php

namespace lx;

class JsMethodDocumentation
{
    private bool $isStatic = false;
    private array $doc = [];
    /** @var array<JsParamDocumentation> */
    private array $params = [];
    
    public function __construct(array $info)
    {
        foreach ($info as $key => $item) {
            if ($key == 'static') {
                $this->isStatic = $item;
                continue;
            }

            if ($key == 'params') {
                foreach ($item as $paramKey => $paramInfo) {
                    $this->params[$paramKey] = new JsParamDocumentation($paramKey, $paramInfo);
                }
                continue;
            }
            
            $this->doc[$key] = $item;
        }
    }
    
    public function hasMarker(string $marker): bool
    {
        return array_key_exists($marker, $this->doc);
    }

    public function getParam(string $key): ?JsParamDocumentation
    {
        return $this->params[$key] ?? null;
    }

    public function toArray(): array
    {
        $params = [];
        foreach ($this->params as $key => $param) {
            $params[$key] = $param->toArray();
        }
        return array_merge($this->doc, [
            'static' => $this->isStatic,
            'params' => $params,
        ]);
    }
}
