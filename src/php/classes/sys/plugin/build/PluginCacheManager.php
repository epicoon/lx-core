<?php

namespace lx;

class PluginCacheManager
{
    const CACHE_NONE = 'none';
    const CACHE_ON = 'on';
    const CACHE_STRICT = 'strict';
    const CACHE_BUILD = 'build';
    const CACHE_SMART = 'smart';

    private Plugin $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function getCacheInfo(): array
    {
        return [
            'type' => $this->plugin->getConfig('cacheType'),
            'exists' => $this->cacheExists(),
        ];
    }

    public function cacheExists(): bool
    {
        $dir = new Directory($this->plugin->conductor->getSnippetsCachePath());
        return $dir->exists();
    }

    public function buildCache(): void
    {
        if (!$this->cacheExists()) {
            $this->renewCache();
        }
    }

    public function renewCache(): void
    {
        $builder = new PluginBuildContext(['plugin' => $this->plugin]);
        $builder->buildCache();
    }

    public function dropCache(): void
    {
        $dir = new Directory($this->plugin->conductor->getSnippetsCachePath());
        $dir->remove();
    }
}
