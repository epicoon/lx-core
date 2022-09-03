<?php

namespace lx;

use lx;

class PageRequestHandler extends RequestHandler
{
    private ?string $title = null;
    private ?string $icon = null;

    protected function defineResponse(): void
    {
        //TODO костыльно (1/2)
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
        $pluginInfo = addcslashes($pluginData['pluginInfo'], '\\`');

        $modulesCode = '';
        $moduleNames = '';
        if (!empty($pluginData['modules'])) {
            $moduleProvider = new JsModuleProvider();
            list ($modulesCode, $modules) = $moduleProvider->compile($pluginData['modules']);
            $modulesCode = addcslashes($modulesCode, '\\`');
            $moduleNames = "'" . implode("','", $modules) . "'";
        }

        $settings = CodeConverterHelper::arrayToJsCode(lx::$app->getSettings());
        $js = "lx.app.start($settings, `$modulesCode`, [$moduleNames], `$pluginInfo`);";

        /** @var HtmlRendererInterface $renderer */
        $renderer = lx::$app->diProcessor->createByInterface(HtmlRendererInterface::class);

        //TODO костыльно (2/2)
        $pageData = $pluginData['page'] ?? [];
        if ($this->title) {
            $pageData['title'] = $this->title;
        }
        if ($this->icon) {
            $pageData['icon'] = $this->icon;
        }

        $result = $renderer
            ->setTemplateType(HttpResponse::OK)
            ->setParams([
                'head' => new HtmlHead($pageData),
                'body' => new HtmlBody($pageData, $js),
            ])->render();

        $this->response->setData($result);
    }
}
