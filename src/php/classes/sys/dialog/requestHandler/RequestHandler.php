<?php

namespace lx;

use lx;

abstract class RequestHandler implements EventListenerInterface
{
    use EventListenerTrait;

    protected HttpRequest $request;
    protected HttpResponseInterface $response;
    protected ?ResourceContext $resourceContext = null;

    protected function __construct(HttpRequest $request, HttpResponseInterface $response)
    {
        $this->request = $request;
        $this->response = $response;
        $this->constructEventListener();
    }

    public static function create(HttpRequest $request, HttpResponseInterface $response): RequestHandler
    {
        switch (true) {
            case $request->isPageLoad():
                return new PageRequestHandler($request, $response);
            case $request->isAjax():
                return new AjaxRequestHandler($request, $response);
            case $request->isCors():
                return new CorsRequestHandler($request, $response);
            default:
                return new CommonRequestHandler($request, $response);
        }
    }

    public static function getEventHandlersMap(): array
    {
        return [
            HttpApplication::EVENT_BEFORE_SEND_RESPONSE => 'beforeSendResponse',
            HttpApplication::EVENT_AFTER_SEND_RESPONSE => 'afterSendResponse',
        ];
    }

    /**
     * Launch of the response preparing
     */
    public function handle(): void
    {
        $this->defineResourceContext();
        if (!$this->resourceContext) {
            $this->setNotFoundResponse();
            return;
        }

        $this->defineResponse();
        $this->prepareResponse();
    }

    protected function beforeSendResponse(Event $event): void
    {
        /** @var HttpResponseInterface $response */
        $response = $event->getPayload('response');
        if ($response->getCode() == HttpResponse::OK) {
            $this->beforeSuccessfulSending();
        } else {
            $this->processProblemResponse($response);
            $this->beforeFailedSending();
        }
    }

    protected function afterSendResponse(Event $event): void
    {
        /** @var HttpResponseInterface $response */
        $response = $event->getPayload('response');
        if ($response->getCode() == HttpResponse::OK) {
            $this->afterSuccessfulSending();
        } else {
            $this->afterFailedSending();
        }
    }

    protected function defineResponse(): void
    {
        $this->resourceContext->setResponse($this->response);
        $this->resourceContext->invoke();
    }

    protected function prepareResponse(): void
    {
        // pass
    }

    protected function processProblemResponse(HttpResponseInterface $response): void
    {
        // pass
    }


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function defineResourceContext(): void
    {
        if (SpecialAjaxRouter::checkRequest()) {
            $ajaxRouter = new SpecialAjaxRouter();
            $resourceContext = $ajaxRouter->route();
            if ($resourceContext !== null) {
                $this->resourceContext = $resourceContext;
            }
        } else {
            /** @var Router $router */
            $router = lx::$app->router;
            if ($router !== null) {
                $resourceContext = $router->route($this->request->getRoute());
                if ($resourceContext !== null) {
                    $this->resourceContext = $resourceContext;
                }
            }
        }
    }

    private function setNotFoundResponse(): void
    {
        $this->response->setCode(HttpResponse::NOT_FOUND);
        $this->response->setData('Resource not found');
    }

    private function beforeSuccessfulSending(): void
    {
        if ($this->resourceContext) {
            $resource = $this->resourceContext->getResource();
            if ($resource) {
                $resource->beforeAction();
                $resource->beforeSuccessfulAction();
            }
        }
    }

    private function beforeFailedSending(): void
    {
        if ($this->resourceContext) {
            $resource = $this->resourceContext->getResource();
            if ($resource) {
                $resource->beforeAction();
                $resource->beforeFailedAction();
            }
        }
    }

    private function afterSuccessfulSending(): void
    {
        if ($this->resourceContext) {
            $resource = $this->resourceContext->getResource();
            if ($resource) {
                $resource->afterSuccessfulAction();
                $resource->afterAction();
            }
        }
    }

    private function afterFailedSending(): void
    {
        if ($this->resourceContext) {
            $resource = $this->resourceContext->getResource();
            if ($resource) {
                $resource->afterFailedAction();
                $resource->afterAction();
            }
        }
    }
}
