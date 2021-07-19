<?php

namespace lx;

class CommonRequestHandler extends RequestHandler
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
        if ($this->app->dialog->isAssetLoad()) {
            $url = $this->app->dialog->getUrl();
            $assetName = 'unknown';
            switch (true) {
                case preg_match('/\.js$/', $url):
                    $assetName = 'javascript file';
            }
            $msg = "Asset ($assetName) \"$url\" not found";
            return $this->app->diProcessor->createByInterface(ResponseInterface::class, [
                'console.error(\'' . $msg . '\');',
                ResponseCodeEnum::NOT_FOUND,
            ]);
        }

        return $response;
    }
}
