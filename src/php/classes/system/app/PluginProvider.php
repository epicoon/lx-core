<?php

namespace lx;

class PluginProvider
{
    private AbstractApplication $app;
    private ?Service $service = null;
    private ?string $serviceName = null;
    private ?string $pluginName = null;
    private ?array $attributes = null;
    private ?string $onLoad = null;
    
    public function __construct(AbstractApplication $app)
    {
        $this->app = $app;
    }
    
    public function setService(Service $service): PluginProvider
    {
        $this->service = $service;
        return $this;
    }
    
    public function setServiceName(string $name): PluginProvider
    {
        $this->serviceName = $name;
        return $this;
    }
    
    public function setPluginName(string $name): PluginProvider
    {
        $this->pluginName = $name;
        return $this;
    }
    
    public function setAttributes(array $attributes): PluginProvider
    {
        $this->attributes = $attributes;
        return $this;
    }
    
    public function setOnLoad(string $code): PluginProvider
    {
        $this->onLoad = $code;
        return $this;
    }
    
    public function getPlugin(): ?Plugin
    {
        $service = $this->getService();
        if (!$service) {
            return null;
        }
        
        $pluginName = $this->getPluginName();
        if (!$pluginName) {
            return null;
        }

        $plugin = $service->getPlugin($pluginName);

        if ($this->attributes) {
            $plugin->addAttributes($this->attributes);
        }

        if ($this->onLoad != '') {
            $plugin->onLoad($this->onLoad);
        }

        return $plugin;
    }
    
    public function getPluginByName(string $name): ?Plugin
    {
        $this->pluginName = $name;
        return $this->getPlugin();
    }
    
    public function getPluginByConfig(array $config): ?Plugin
    {
        $this->service = $config['service'] ?? null;
        $this->serviceName = $config['serviceName'] ?? null;
        $this->pluginName = $config['name'] ?? $config['plugin'] ?? $config['pluginName'] ?? null;
        $this->attributes = $config['attributes'] ?? null;
        $this->onLoad = $config['onLoad'] ?? null;
        return $this->getPlugin();
    }
    
    
    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */ 
    
    private function getService(): ?Service
    {
        if ($this->service) {
            return $this->service;
        }
        
        if ($this->serviceName) {
            $this->service = $this->app->getService($this->serviceName);
            return $this->service;
        }
        
        if ($this->pluginName && strpos($this->pluginName, ':')) {
            $arr = explode(':', $this->pluginName);
            if (count($arr) != 2) {
                return null;
            }
            $this->serviceName = $arr[0];
            $this->pluginName = $arr[1];
            $this->service = $this->app->getService($this->serviceName);
            return $this->service;
        }

        return null;
    }
    
    private function getPluginName(): ?string
    {
        if ($this->pluginName && strpos($this->pluginName, ':')) {
            $arr = explode(':', $this->pluginName);
            if (count($arr) != 2) {
                return null;
            }
            $this->serviceName = $arr[0];
            $this->pluginName = $arr[1];
            $this->service = $this->app->getService($this->serviceName);
        }
        
        return $this->pluginName;
    }
}
