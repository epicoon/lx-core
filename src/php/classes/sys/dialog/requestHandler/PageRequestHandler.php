<?php

namespace lx;

use lx;

class PageRequestHandler extends RequestHandler
{
    private ?string $title = null;
    private ?string $icon = null;

    protected function defineResponse(): void
    {
        if ($this->resourceContext->isPlugin()) {
            $plugin = $this->resourceContext->getPlugin();
            $this->title = $plugin->getTitle();
            $this->icon = $plugin->getIcon();
        }

        $this->resourceContext->setResponse($this->response);
        $this->resourceContext->invoke();

        if ($this->response->requireAuthorization()) {
            $resourceContext = lx::$app->authenticationGate->responseToAuthenticate() ?? null;
            if ($resourceContext && $resourceContext->isPlugin()) {
                $this->resourceContext = $resourceContext;
                $this->resourceContext->setResponse($this->response);
                $this->resourceContext->invoke();
            }
        }
    }

    protected function prepareResponse(): void
    {
        if ($this->resourceContext && $this->resourceContext->isPlugin()) {
            $this->renderPlugin();
        }
    }
    
    protected function processProblemResponse(HttpResponseInterface $response): void
    {
        /** @var HtmlRendererInterface $renderer */
        $renderer = lx::$app->diProcessor->createByInterface(HtmlRendererInterface::class);
        $result = $renderer
            ->setTemplateType($response->getCode())
            ->render();
        $response->setData($result);
    }

    private function renderPlugin(): void
    {
        $pluginData = $this->response->getData();

        $pageData = $this->extractPageData($pluginData);
        $pluginInfo = addcslashes($pluginData['pluginInfo'], '\\`');
        list ($compiledModules, $modulesCode) = $this->extractModulesCode($pluginData);
        $moduleNames = (!empty($compiledModules))
            ? "'" . implode("','", $compiledModules) . "'"
            : '';
        $appConfig = CodeConverterHelper::arrayToJsCode(lx::$app->getBuildData());
        $js = "lx.app.start($appConfig, `$modulesCode`, [$moduleNames], `$pluginInfo`);";
        //TODO костыль, т.к. в PluginBuildContext собираются зависимости от модулей без дерева взаимозависимостей самих модулей
        $pageData['moduleCss'] = lx::$app->jsModules->getModulesCss($compiledModules);

        /** @var HtmlRendererInterface $renderer */
        $renderer = lx::$app->diProcessor->createByInterface(HtmlRendererInterface::class);
        $result = $renderer
            ->setTemplateType(HttpResponse::OK)
            ->setParams([
                'head' => new HtmlHead($pageData),
                'body' => new HtmlBody($pageData, $js),
            ])->render();

        $this->response->setData($result);
    }

    private function extractModulesCode(array $pluginData): array
    {
        $moduleNames = $pluginData['modules'];
        $modulesCode = '';
        $modules = [];
        if (!empty($moduleNames)) {
            $moduleProvider = new JsModuleProvider();
            list ($modulesCode, $modules) = $moduleProvider->compile($moduleNames);
            $modulesCode = addcslashes($modulesCode, '\\`');
            $compiledModuleNames = "'" . implode("','", $modules) . "'";
        }
        return [$modules, $modulesCode];
    }

    private function extractPageData(array $pluginData): array
    {
        $pageData = $pluginData['page'] ?? [];
        if ($this->title) {
            $pageData['title'] = $this->title;
        }
        if ($this->icon) {
            $pageData['icon'] = $this->icon;
        }
        return $pageData;
    }
}
