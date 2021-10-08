<?php

namespace lx;

use lx;

class ServiceProvider
{
    private ?string $serviceName = null;
    private ?string $fileName = null;
    private ?FileInterface $file = null;

    public function setServiceName(string $name): ServiceProvider
    {
        $this->serviceName = $name;
        return $this;
    }
    
    public function setFileName(string $fileName): ServiceProvider
    {
        $this->fileName = $fileName;
        return $this;
    }
    
    public function setFile(FileInterface $file): ServiceProvider
    {
        $this->file = $file;
        return $this;
    }
    
    public function getService(): ?Service
    {
        if ($this->serviceName && lx::$app->services->exists($this->serviceName)) {
            return lx::$app->services->get($this->serviceName);
        }
        
        if ($this->file) {
            $this->fileName = $this->file->getPath();
        }
        
        if ($this->fileName) {
            $filePath = lx::$app->conductor->getFullPath($this->fileName);

            $map = Autoloader::getInstance()->map->packages;
            foreach ($map as $name => $servicePath) {
                $fullServicePath = addcslashes(lx::$app->sitePath . '/' . $servicePath, '/');
                if (preg_match('/^' . $fullServicePath . '\//', $filePath)) {
                    return lx::$app->services->get($name);
                }
            }
        }
        
        return null;
    }
    
    public function getServiceByName(string $name): ?Service
    {
        $this->serviceName = $name;
        return $this->getService();
    }
}
