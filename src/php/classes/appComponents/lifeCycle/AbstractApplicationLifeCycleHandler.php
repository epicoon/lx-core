<?php

namespace lx;

abstract class AbstractApplicationLifeCycleHandler
    implements ApplicationLifeCycleInterface, FusionComponentInterface, EventListenerInterface
{
    use FusionComponentTrait;
    use EventListenerTrait;

    public static function getEventHandlersMap(): array
    {
        return [
            AbstractApplication::EVENT_BEFORE_RUN => 'beforeApplicationRun',
            AbstractApplication::EVENT_AFTER_RUN => 'afterApplicationRun',
            Plugin::EVENT_BEFORE_GET_AUTO_LINKS => 'beforeGetAutoLinkPathes',
            Plugin::EVENT_BEFORE_GET_CSS_ASSETS => 'beforeGetPluginCssAssets',
        ];
    }

    abstract public function beforeApplicationRun(): void;
    abstract public function afterApplicationRun(): void;
    abstract public function beforeGetPluginCssAssets(Plugin $plugin): void;
    abstract public function beforeGetAutoLinkPathes(array $originalPathes, array $linkPathes): void;
}
