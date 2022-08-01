<?php

namespace lx;

use lx;

class CommonRequestHandler extends RequestHandler
{
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

            $response->setCode(HttpResponse::NOT_FOUND);
            $response->setData('console.error(\'' . $msg . '\');');
        }
    }
}
