<?php

namespace lx;

/**
 * Class RequestHandler
 * @package lx
 */
class RequestHandler
{
    use ObjectTrait;
	use ApplicationToolTrait;

	/** @var SourceContext */
	private $sourceContext;

	/** @var ResponseInterface */
	private $response;

	/**
	 * Launch of the response preparing
	 */
	public function run()
	{
        $this->defineSourceContext();
        $this->defineResponse();
	}

	/**
	 * Send the response
	 */
	public function send()
	{
	    if (!$this->sourceContext) {
	        $r = 1;
        }

        if ($this->sourceContext && $this->sourceContext->isPlugin() && $this->app->dialog->isPageLoad()) {
            $response = $this->renderPlugin();
        } else {
            $response = $this->response;
        }

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


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	private function defineSourceContext()
	{
		if (SpecialAjaxRouter::checkDialog()) {
			$ajaxRouter = new SpecialAjaxRouter();
			$sourceContext = $ajaxRouter->route();
			if ($sourceContext !== false) {
				$this->sourceContext = $sourceContext;
			}
		} else {
			$router = $this->app->router;
			if ($router !== null) {
				$sourceContext = $router->route();
				if ($sourceContext !== false) {
					$this->sourceContext = $sourceContext;
				}
			}
		}
	}

	private function defineResponse()
    {
        if (!isset($this->sourceContext)) {
            $this->response = $this->app->diProcessor->createByInterface(ResponseInterface::class, [
                'Resource not found',
                ResponseCodeEnum::NOT_FOUND,
            ]);
            return;
        }

        $response = $this->sourceContext->invoke();
        if ($response->hasErrors()) {
            if ($response->getCode() == ResponseCodeEnum::FORBIDDEN
                && $this->app->dialog->isPageLoad()
                && $this->app->user->isGuest()
            ) {
                $sourceContext = $this->app->authenticationGate->responseToAuthenticate() ?? null;
                if ($sourceContext && $sourceContext->isPlugin()) {
                    $this->sourceContext = $sourceContext;
                    $response = $this->sourceContext->invoke();
                }
            }
        }

        $this->response = $response;
    }

    /**
     * @param ResponseInterface $response
     */
	private function processProblemResponse($response)
	{
		if ($this->app->dialog->isPageLoad()) {
            $renderer = $this->app->diProcessor->createByInterface(RendererInterface::class);
            $result = $renderer->render($response->getCode() . '.php');

            /** @var ResponseInterface $response */
            $newResponse = $this->app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
            return $newResponse;
		}

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

	/**
     * @return ResponseInterface
	 */
	private function renderPlugin()
	{
	    $pluginData = $this->response->getData();
		$pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

		$modules = '';
		if (!empty($pluginData['modules'])) {
			$moduleProvider = new JsModuleProvider();
			$modules = $moduleProvider->getModulesCode($pluginData['modules']);
			$modules = addcslashes($modules, '\\');
		}

		list($jsBootstrap, $jsMain) = $this->app->getCommonJs();
		$settings = ArrayHelper::arrayToJsCode($this->app->getSettings());
		$js = "lx.start($settings, `$modules`, `$jsBootstrap`, `$pluginInfo`, `$jsMain`);";

		
		
		$renderer = $this->app->diProcessor->createByInterface(RendererInterface::class);
		$result = $renderer->render('200.php', [
            'head' => new HtmlHead($pluginData['page']),
            'body' => new HtmlBody($pluginData['page'], $js),
        ]);

		/** @var ResponseInterface $response */
		$response = $this->app->diProcessor->createByInterface(ResponseInterface::class, [$result]);
		return $response;
	}

	private function beforeSuccessfulSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('beforeSending');
			$this->sourceContext->invoke('beforeSuccessfulSending');
		}
	}

	private function beforeFailedSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('beforeSending');
			$this->sourceContext->invoke('beforeFailedSending');
		}
	}

	private function afterSuccessfulSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('afterSuccessfulSending');
			$this->sourceContext->invoke('afterSending');
		}
	}

	private function afterFailedSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('afterFailedSending');
			$this->sourceContext->invoke('afterSending');
		}
	}
}
