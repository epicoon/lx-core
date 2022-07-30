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

        $response = $this->resourceContext->invoke();

        if ($response->requireAuthorization()) {
            $resourceContext = lx::$app->authenticationGate->responseToAuthenticate() ?? null;
            if ($resourceContext && $resourceContext->isPlugin()) {
                $this->resourceContext = $resourceContext;
                $response = $this->resourceContext->invoke();
            }
        }

        $this->response = $response;
    }

    protected function prepareResponse(): HttpResponseInterface
    {
        if ($this->resourceContext && $this->resourceContext->isPlugin()) {
            return $this->renderPlugin();
        }

        return $this->response;
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

    private function renderPlugin(): HttpResponseInterface
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
        $js = "lx.start($settings, `$modulesCode`, [$moduleNames], `$pluginInfo`);";

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
            ->setTemplateType(ResponseCodeEnum::OK)
            ->setParams([
                'head' => new HtmlHead($pageData),
                'body' => new HtmlBody($pageData, $js),
            ])->render();

        /** @var HttpResponseInterface $response */
        $response = lx::$app->diProcessor->createByInterface(HttpResponseInterface::class, [$result]);
        return $response;
    }
}
