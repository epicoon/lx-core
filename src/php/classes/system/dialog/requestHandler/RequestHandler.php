<?php

namespace lx;

use lx;

abstract class RequestHandler implements EventListenerInterface
{
    use EventListenerTrait;

    protected HttpRequest $request;
    protected ?ResourceContext $resourceContext = null;
    protected ?HttpResponseInterface $response = null;

    protected function __construct(HttpRequest $request)
    {
        $this->request = $request;
    }

    public static function create(HttpRequest $request): RequestHandler
    {
        switch (true) {
            case $request->isPageLoad():
                return new PageRequestHandler($request);
            case $request->isAjax():
                return new AjaxRequestHandler($request);
            case $request->isCors():
                return new CorsRequestHandler($request);
            default:
                return new CommonRequestHandler($request);
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
    public function handle(): HttpResponseInterface
    {
        $this->defineResourceContext();
        if (!$this->resourceContext) {
            $this->setNotFoundResponse();
        } else {
            $this->defineResponse();
        }

        $this->response = $this->prepareResponse();
        return $this->response;
    }

    protected function beforeSendResponse(HttpResponseInterface $response): void
    {
        if ($response->getCode() == ResponseCodeEnum::OK) {
            $this->beforeSuccessfulSending();
        } else {
            $this->processProblemResponse($response);
            $this->beforeFailedSending();
        }
    }

    protected function afterSendResponse(HttpResponseInterface $response): void
    {
        if ($response->getCode() == ResponseCodeEnum::OK) {
            $this->afterSuccessfulSending();
        } else {
            $this->afterFailedSending();
        }
    }

    abstract protected function defineResponse(): void;
    abstract protected function prepareResponse(): HttpResponseInterface;
    abstract protected function processProblemResponse(HttpResponseInterface $response): void;


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
        $this->response = lx::$app->diProcessor->createByInterface(HttpResponseInterface::class, [
            'Resource not found',
            ResponseCodeEnum::NOT_FOUND,
        ]);
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
