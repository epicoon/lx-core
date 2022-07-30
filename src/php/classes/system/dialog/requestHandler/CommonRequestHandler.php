<?php

namespace lx;

use lx;

class CommonRequestHandler extends RequestHandler
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
        if ($this->request->isAssetLoad()) {
            $url = $this->request->getUrl();
            $assetName = 'unknown';
            switch (true) {
                case preg_match('/\.js$/', $url):
                    $assetName = 'javascript file';
            }
            $msg = "Asset ($assetName) \"$url\" not found";

            $response->setCode(ResponseCodeEnum::NOT_FOUND);
            $response->setData('console.error(\'' . $msg . '\');');
        }
    }
}
