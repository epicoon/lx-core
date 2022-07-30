<?php

namespace lx;

class AjaxRequestHandler extends RequestHandler
{
    protected function defineResponse(): void
    {
        $this->response = $this->resourceContext->invoke();
    }

    protected function prepareResponse(): HttpResponseInterface
    {
        return $this->response;
    }

    protected function processProblemResponse(HttpResponseInterface $response): void
    {
        // pass
    }
}
