<?php

namespace lx;

use lx;

class JsModuleInfo
{
    private string $name;
    private ?string $serviceName;
    private ?string $path;
    private array $metaData;
    private ?array $doc;
    
    public function __construct(string $name, array $info)
    {
        $this->name = $name;
        $this->serviceName = $info['service'] ?? null;
        $this->path = $info['path'] ?? null;
        $this->metaData = $info['data'] ?? [];
        $this->doc = null;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getPath(): ?string
    {
        return $this->path;
    }
    
    public function getService(): ?Service
    {
        if (!$this->serviceName) {
            return null;
        }

        return lx::$app->getService($this->serviceName);
    }
    
    public function hasMetaData(?string $key = null): bool
    {
        if ($key === null) {
            return !empty($this->metaData);
        }
        
        return array_key_exists($key, $this->metaData);
    }

    /**
     * @return mixed
     */
    public function getMetaData(string $key)
    {
        return $this->metaData[$key] ?? null;
    }

    public function getDocumentation(): array
    {
        if ($this->doc === null) {
            $parser = new ModuleDocParser();
            $parser->setName($this->getName())
                ->setFile(new File($this->getPath()));
            $classes = $parser->parse();
            $this->doc = [];
            foreach ($classes as $classInfo) {
                $doc = new JsClassDocumentation($this->getName(), $classInfo);
                $this->doc[$doc->getClassName()] = $doc;
            }
        }
        
        return $this->doc;
    }
}
