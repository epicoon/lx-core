<?php

namespace lx;

/**
 * Class CommonRequestHandler
 * @package lx
 */
class CommonRequestHandler extends RequestHandler
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
