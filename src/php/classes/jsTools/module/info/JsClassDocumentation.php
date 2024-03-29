<?php

namespace lx;

class JsClassDocumentation
{
    private string $moduleName;
    private string $className;
    private string $namespace;
    private string $cleanClassName;
    private string $extends;
    private array $doc;
    /** @var array<JsMethodDocumentation> */
    private array $methods;
    
    public function __construct(string $moduleName, array $info)
    {
        $this->moduleName = $moduleName;
        $this->className = $info['fullName'] ?? '';
        $this->namespace = $info['namespace'] ?? '';
        $this->cleanClassName = $info['name'] ?? '';
        $this->extends = $info['extends'] ?? '';
        $this->doc = $info['doc'] ?? [];
        $methods = $info['methods'] ?? [];
        $this->methods = [];
        foreach ($methods as $methodName => $methodInfo) {
            $this->methods[$methodName] = new JsMethodDocumentation($methodInfo);
        }
    }

    public function getClassName(): string
    {
        return $this->className;
    }
    
    public function hasMarker(string $marker): bool
    {
        return array_key_exists($marker, $this->doc);
    }

    /**
     * @return array<JsMethodDocumentation>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getMethod(string $name): ?JsMethodDocumentation
    {
        return $this->methods[$name] ?? null;
    }

    public function toArray(): array
    {
        $result = [
            'name' => $this->cleanClassName,
            'namespace' => $this->namespace,
            'fullName' => $this->className,
            'extends' => $this->extends,
            'doc' => $this->doc,
            'methods' => [],
        ];
        
        foreach ($this->methods as $methodName => $method) {
            $result['methods'][$methodName] = $method->toArray();
        }
        
        return $result;
    }
}
