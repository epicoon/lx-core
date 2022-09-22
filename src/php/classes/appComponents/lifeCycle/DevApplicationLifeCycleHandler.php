<?php

namespace lx;

use lx;

class DevApplicationLifeCycleHandler
    implements HttpApplicationLifeCycleInterface, FusionComponentInterface, EventListenerInterface
{
    use FusionComponentTrait;
    use EventListenerTrait;

    public static function getEventHandlersMap(): array
    {
        return [
            AbstractApplication::EVENT_BEFORE_RUN => 'beforeApplicationRun',
            AbstractApplication::EVENT_AFTER_RUN => 'afterApplicationRun',
            HttpApplication::EVENT_BEFORE_HANDLE_REQUEST => 'beforeHandleRequest',
            PluginAssetProvider::EVENT_BEFORE_GET_AUTO_LINKS => 'beforeGetAutoLinkPathes',
            PluginAssetProvider::EVENT_BEFORE_GET_CSS_ASSETS => 'beforeGetPluginCssAssets',
            JsModulesComponent::EVENT_BEFORE_COMPILE_MODULE_CODE => 'beforeCompileModuleCode',
        ];
    }

    public function beforeApplicationRun(Event $event): void
    {
        // pass
    }

    public function afterApplicationRun(Event $event): void
    {
        // pass
    }

    public function beforeHandleRequest(Event $event): void
    {
        /** @var HttpRequest $request */
        $request = $event->getPayload('request');
        if (!$request->isPageLoad()) {
            return;
        }

        $compiler = new AppAssetCompiler();
        $compiler->compileJsCore();
        $compiler->compileAppCss();
    }

    public function beforeGetAutoLinkPathes(Event $event): void
    {
        WebAssetHelper::createLinks(
            $event->getPayload('origins'),
            $event->getPayload('links')
        );
    }

    public function beforeGetPluginCssAssets(Event $event): void
    {
        if (lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_NONE)) {
            return;
        }

        /** @var Plugin $plugin */
        $plugin = $event->getPayload('plugin');
        $cssCompiler = new PluginCssCompiler($plugin);
        $cssCompiler->compile();
    }
    
    public function beforeCompileModuleCode(Event $event): void
    {
        $moduleName = $event->getPayload('moduleName');

        if ($moduleName == 'lx.test.TestModule') {
            $e = 1;
        }

        if (!lx::$app->jsModules->isModuleExist($moduleName)) {
            $actualizer = new JsModuleMapActualizer();
            $actualizer->renewProjectServices();
            lx::$app->jsModules->reset();
        }

        



        
    }
}
