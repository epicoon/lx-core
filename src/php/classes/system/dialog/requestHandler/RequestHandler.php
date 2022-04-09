<?php

namespace lx;

use lx;

abstract class RequestHandler
{
    protected ?ResourceContext $resourceContext = null;
    protected ?ResponseInterface $response = null;

    public static function create(): RequestHandler
    {
        if (lx::$app->dialog->isPageLoad()) {
            return new PageRequestHandler();
        }

        if (lx::$app->dialog->isAjax()) {
            return new AjaxRequestHandler();
        }

        return new CommonRequestHandler();
    }

    /**
     * Launch of the response preparing
     */
    public function run(): void
    {
        $this->defineResourceContext();
        if (!$this->resourceContext) {
            $this->setNotFoundResponse();
        } else {
            $this->defineResponse();
        }
    }

    /**
     * Send the response
     */
    public function send(): void
    {
        $response = $this->prepareResponse();

        if ($response->getCode() == ResponseCodeEnum::OK) {
            $this->beforeSuccessfulSending();
            lx::$app->dialog->send($response);
            $this->afterSuccessfulSending();
        } else {
            $response = $this->processProblemResponse($response);
            $this->beforeFailedSending();
            lx::$app->dialog->send($response);
            $this->afterFailedSending();
        }
    }

    abstract protected function defineResponse(): void;
    abstract protected function prepareResponse(): ResponseInterface;
    abstract protected function processProblemResponse(ResponseInterface $response): ResponseInterface;


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private function defineResourceContext(): void
    {
        if (SpecialAjaxRouter::checkDialog()) {
            $ajaxRouter = new SpecialAjaxRouter();
            $resourceContext = $ajaxRouter->route();
            if ($resourceContext !== null) {
                $this->resourceContext = $resourceContext;
            }
        } else {
            $router = lx::$app->router;
            if ($router !== null) {
                $resourceContext = $router->route();
                if ($resourceContext !== null) {
                    $this->resourceContext = $resourceContext;
                }
            }
        }
    }

    private function setNotFoundResponse(): void
    {
        $this->response = lx::$app->diProcessor->createByInterface(ResponseInterface::class, [
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
