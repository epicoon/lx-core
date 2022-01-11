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

        if ($response->isForbidden() && lx::$app->user->isGuest()) {
            $resourceContext = lx::$app->authenticationGate->responseToAuthenticate() ?? null;
            if ($resourceContext && $resourceContext->isPlugin()) {
                $this->resourceContext = $resourceContext;
                $response = $this->resourceContext->invoke();
            }
        }

        $this->response = $response;
    }

    protected function prepareResponse(): ResponseInterface
    {
        if ($this->resourceContext && $this->resourceContext->isPlugin()) {
            return $this->renderPlugin();
        }

        return $this->response;
    }
    
    protected function processProblemResponse(ResponseInterface $response): ResponseInterface
    {
        /** @var HtmlRendererInterface $renderer */
        $renderer = lx::$app->diProcessor->createByInterface(HtmlRendererInterface::class);
        $result = $renderer
            ->setTemplateType($response->getCode())
            ->render();

        /** @var ResponseInterface $response */
        $newResponse = lx::$app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
        return $newResponse;
    }

    private function renderPlugin(): ResponseInterface
    {
        $pluginData = $this->response->getData();
        $pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

        $modules = '';
        $moduleNames = '';
        if (!empty($pluginData['modules'])) {
            $moduleProvider = new JsModuleProvider();
            $modules = $moduleProvider->getModulesCode($pluginData['modules']);
            $modules = addcslashes($modules, '\\');
            $moduleNames = "'" . implode("','", $pluginData['modules']) . "'";
        }

        list($jsBootstrap, $jsMain) = lx::$app->getCommonJs();
        $settings = CodeConverterHelper::arrayToJsCode(lx::$app->getSettings());
        $js = "lx.start($settings, `$modules`, [$moduleNames], `$jsBootstrap`, `$pluginInfo`, `$jsMain`);";

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

        /** @var ResponseInterface $response */
        $response = lx::$app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
        return $response;
    }
}
