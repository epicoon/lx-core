<?php

namespace lx;

/**
 * @property-read HttpAssetsManager $assets
 * @property-read Router $router
 * @property-read Dialog $dialog
 */
class HttpApplication extends BaseApplication
{
    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'assets' => HttpAssetsManager::class,
            'router' => Router::class,
            'dialog' => Dialog::class,
            'user' => UserInterface::class,
        ]);
    }

	public function run(): void
	{
        try {
            $this->events->trigger(self::EVENT_BEFORE_RUN);

            $this->authenticateUser();
            $requestHandler = RequestHandler::create();
            $requestHandler->run();
            $requestHandler->send();

            $this->events->trigger(self::EVENT_AFTER_RUN);
        } catch (\Throwable $exception) {
            //TODO обработчик ошибок должен быть отдельно - какой-то класс/компонент?
            if ($this->isProd()) {
                $this->logger->error($exception, [
                    'URL' => $this->dialog->getUrl(),
                ]);
            } else {
                $errorString = ErrorHelper::renderErrorString($exception, [
                    'URL' => $this->dialog->getUrl(),
                ]);
                if ($this->dialog->isPageLoad()) {
                    /** @var HtmlRendererInterface $renderer */
                    $renderer = $this->diProcessor->createByInterface(HtmlRendererInterface::class);
                    $result = $renderer
                        ->setTemplateType(500)
                        ->setParams([
                            'error' => $errorString
                        ])->render();
                    /** @var ResponseInterface $response */
                    $response = $this->diProcessor->createByInterface(ResponseInterface::class, [$result]);
                } else {
                    \lx::dump($errorString);
                    $response = $this->diProcessor->createByInterface(ResponseInterface::class, [
                        '',
                        ResponseCodeEnum::SERVER_ERROR
                    ]);
                }
                $this->dialog->send($response);
            }
        }
	}

	public function getCommonJs(): array
	{
		$compiler = new JsCompiler();

		$jsBootstrap = $this->compileJsBootstrap($compiler);
		$jsMain = $this->compileJsMain($compiler);
		$jsBootstrap = addcslashes($jsBootstrap, '\\');
		$jsMain = addcslashes($jsMain, '\\');

		return [$jsBootstrap, $jsMain];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function authenticateUser(): void
	{
		if ($this->user && $this->userManager && $this->authenticationGate) {
			$this->authenticationGate->authenticateUser();
		}
	}

	private function compileJsBootstrap(JsCompiler $compiler): string
	{
		$path = $this->getConfig('jsBootstrap');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}

	private function compileJsMain(JsCompiler $compiler): string
	{
		$path = $this->getConfig('jsMain');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}
}
