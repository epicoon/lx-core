<?php

namespace lx;

/**
 * Class PageRequestHandler
 * @package lx
 */
class PageRequestHandler extends RequestHandler
{
    /** @var string|null */
    private $title = null;

    /** @var string|null */
    private $icon = null;

    /**
     * @return void
     */
    protected function defineResponse()
    {
        //TODO костыльно (1/2)
        if ($this->resourceContext->isPlugin()) {
            $plugin = $this->resourceContext->getPlugin();
            $this->title = $plugin->getTitle();
            $this->icon = $plugin->getIcon();
        }

        $response = $this->resourceContext->invoke();

        if ($response->isForbidden() && $this->app->user->isGuest()) {
            $resourceContext = $this->app->authenticationGate->responseToAuthenticate() ?? null;
            if ($resourceContext && $resourceContext->isPlugin()) {
                $this->resourceContext = $resourceContext;
                $response = $this->resourceContext->invoke();
            }
        }

        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    protected function prepareResponse()
    {
        if ($this->resourceContext && $this->resourceContext->isPlugin()) {
            return $this->renderPlugin();
        }

        return $this->response;
    }
    
    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function processProblemResponse($response)
    {
        $renderer = $this->app->diProcessor->createByInterface(RendererInterface::class);
        $result = $renderer->render($response->getCode() . '.php');

        /** @var ResponseInterface $response */
        $newResponse = $this->app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
        return $newResponse;
    }

    /**
     * @return ResponseInterface
     */
    private function renderPlugin()
    {
        $pluginData = $this->response->getData();
        $pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

        $modules = '';
        if (!empty($pluginData['modules'])) {
            $moduleProvider = new JsModuleProvider();
            $modules = $moduleProvider->getModulesCode($pluginData['modules']);
            $modules = addcslashes($modules, '\\');
        }

        list($jsBootstrap, $jsMain) = $this->app->getCommonJs();
        $settings = ArrayHelper::arrayToJsCode($this->app->getSettings());
        $js = "lx.start($settings, `$modules`, `$jsBootstrap`, `$pluginInfo`, `$jsMain`);";

        $renderer = $this->app->diProcessor->createByInterface(RendererInterface::class);

        //TODO костыльно (2/2)
        $pageData = $pluginData['page'] ?? [];
        if ($this->title) {
            $pageData['title'] = $this->title;
        }
        if ($this->icon) {
            $pageData['icon'] = $this->icon;
        }

        $result = $renderer->render('200.php', [
            'head' => new HtmlHead($pageData),
            'body' => new HtmlBody($pageData, $js),
        ]);

        /** @var ResponseInterface $response */
        $response = $this->app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
        return $response;
    }
}
