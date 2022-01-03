<?php

namespace lx;

interface ApplicationLifeCycleInterface
{
    public function beforeApplicationRun(): void;
    public function afterApplicationRun(): void;
    public function beforeGetPluginCssAssets(Plugin $plugin): void;
    public function beforeReturnAutoLinkPathes(array $originalPathes, array $linkPathes): void;
}
