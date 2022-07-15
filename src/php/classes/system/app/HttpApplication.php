<?php

namespace lx;

/**
 * @property-read Router $router
 * @property-read Dialog $dialog
 */
class HttpApplication extends AbstractApplication
{
    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
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

    public function getSettings(): array
    {
        if (!array_key_exists('cssPreset', $this->settings)) {
            $this->settings['cssPreset'] = $this->presetManager->getDefaultCssPreset();
        }

        if (!array_key_exists('assetBuildType', $this->settings)) {
            $this->settings['assetBuildType'] = $this->presetManager->getBuildType();
        }

        return $this->settings;
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
}
