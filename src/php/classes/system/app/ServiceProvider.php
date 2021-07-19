<?php

namespace lx;

class ServiceProvider
{
    private AbstractApplication $app;
    private ?string $serviceName = null;
    private ?string $fileName = null;
    private ?FileInterface $file = null;

    public function __construct(AbstractApplication $app)
    {
        $this->app = $app;
    }
    
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
        if ($this->serviceName && $this->app->services->exists($this->serviceName)) {
            return $this->app->services->get($this->serviceName);
        }
        
        if ($this->file) {
            $this->fileName = $this->file->getPath();
        }
        
        if ($this->fileName) {
            $filePath = $this->app->conductor->getFullPath($this->fileName);

            $map = Autoloader::getInstance()->map->packages;
            foreach ($map as $name => $servicePath) {
                $fullServicePath = addcslashes($this->app->sitePath . '/' . $servicePath, '/');
                if (preg_match('/^' . $fullServicePath . '\//', $filePath)) {
                    return $this->app->services->get($name);
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
