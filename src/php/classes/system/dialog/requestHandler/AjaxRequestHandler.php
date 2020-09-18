<?php

namespace lx;

/**
 * Class AjaxRequestHandler
 * @package lx
 */
class AjaxRequestHandler extends RequestHandler
{
    /**
     * @return void
     */
    protected function defineResponse()
    {
        $this->response = $this->resourceContext->invoke();
    }

    /**
     * @return ResponseInterface
     */
    protected function prepareResponse()
    {
        return $this->response;
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function processProblemResponse($response)
    {
        return $response;
    }
}
