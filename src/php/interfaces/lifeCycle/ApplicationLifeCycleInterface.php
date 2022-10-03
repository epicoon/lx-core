<?php

namespace lx;

interface ApplicationLifeCycleInterface
{
    public function beforeApplicationRun(Event $event): void;
    public function afterApplicationRun(Event $event): void;
    public function beforeGetPluginCssAssets(Event $event): void;
    public function beforeGetAutoLinkPathes(Event $event): void;
    public function beforeCompileModuleCode(Event $event): void;
    public function beforeGetModuleCssAssets(Event $event): void;
}
