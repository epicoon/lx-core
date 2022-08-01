<?php

namespace lx;

class CorsRequestHandler extends CommonRequestHandler
{
    protected function beforeSendResponse(HttpResponseInterface $response): void
    {
        $this->addCorsHeaders();
        parent::beforeSendResponse($response);
    }

    private function addCorsHeaders(): void
    {
        /** @var CorsProcessor|null $corsProcessor */
        $corsProcessor = lx::$app->corsProcessor;
        if (!$corsProcessor) {
            return;
        }

        $requestHeaders = [
            'origin' => $this->request->getHeader('ORIGIN'),
            'method' => $this->request->getHeader('Access-Control-Request-Method'),
            'headers' => $this->request->getHeader('Access-Control-Request-Headers'),
        ];

        $headers = $corsProcessor->getHeaders($requestHeaders);
        foreach ($headers as $header) {
            header($header);
        }
    }
}
