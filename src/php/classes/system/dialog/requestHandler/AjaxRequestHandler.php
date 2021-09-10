<?php

namespace lx;

class AjaxRequestHandler extends RequestHandler
{
    protected function defineResponse(): void
    {
        $this->response = $this->resourceContext->invoke();
    }

    protected function prepareResponse(): ResponseInterface
    {
        return $this->response;
    }

    protected function processProblemResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}
