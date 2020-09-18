<?php

namespace lx;

use lx;

/**
 * Class RequestHandler
 * @package lx
 */
abstract class RequestHandler
{
    use ObjectTrait;
    use ApplicationToolTrait;

    /** @var ResourceContext */
    protected $resourceContext;

    /** @var ResponseInterface */
    protected $response;

    /**
     * @return AjaxRequestHandler|CommonRequestHandler|PageRequestHandler
     */
    public static function create()
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
    public function run()
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
    public function send()
    {
        $response = $this->prepareResponse();

        if ($response->getCode() == ResponseCodeEnum::OK) {
            $this->beforeSuccessfulSending();
            $this->app->dialog->send($response);
            $this->afterSuccessfulSending();
        } else {
            $response = $this->processProblemResponse($response);
            $this->beforeFailedSending();
            $this->app->dialog->send($response);
            $this->afterFailedSending();
        }
    }

    /**
     * @return void
     */
    abstract protected function defineResponse();

    /**
     * @return ResponseInterface
     */
    abstract protected function prepareResponse();

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    abstract protected function processProblemResponse($response);


    /*******************************************************************************************************************
     * PRIVATE
     ******************************************************************************************************************/

    private function defineResourceContext()
    {
        if (SpecialAjaxRouter::checkDialog()) {
            $ajaxRouter = new SpecialAjaxRouter();
            $resourceContext = $ajaxRouter->route();
            if ($resourceContext !== false) {
                $this->resourceContext = $resourceContext;
            }
        } else {
            $router = $this->app->router;
            if ($router !== null) {
                $resourceContext = $router->route();
                if ($resourceContext !== false) {
                    $this->resourceContext = $resourceContext;
                }
            }
        }
    }

    private function setNotFoundResponse()
    {
        $this->response = $this->app->diProcessor->createByInterface(ResponseInterface::class, [
            'Resource not found',
            ResponseCodeEnum::NOT_FOUND,
        ]);
    }

    private function beforeSuccessfulSending()
    {
        if ($this->resourceContext) {
            $this->resourceContext->invoke('beforeSending');
            $this->resourceContext->invoke('beforeSuccessfulSending');
        }
    }

    private function beforeFailedSending()
    {
        if ($this->resourceContext) {
            $this->resourceContext->invoke('beforeSending');
            $this->resourceContext->invoke('beforeFailedSending');
        }
    }

    private function afterSuccessfulSending()
    {
        if ($this->resourceContext) {
            $this->resourceContext->invoke('afterSuccessfulSending');
            $this->resourceContext->invoke('afterSending');
        }
    }

    private function afterFailedSending()
    {
        if ($this->resourceContext) {
            $this->resourceContext->invoke('afterFailedSending');
            $this->resourceContext->invoke('afterSending');
        }
    }
}
