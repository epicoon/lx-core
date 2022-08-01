<?php

namespace lx;

/**
 * @property-read Router $router
 * @property-read HttpRequest $request
 * @property-read HttpResponse $response
 * @property-read Cookie $cookie
 */
class HttpApplication extends AbstractApplication
{
    const EVENT_BEFORE_HANDLE_REQUEST = 'beforeHandleRequest';
    const EVENT_BEFORE_SEND_RESPONSE = 'beforeSendResponse';
    const EVENT_AFTER_SEND_RESPONSE = 'afterSendResponse';

    public function getFusionComponentTypes(): array
    {
        return array_merge(parent::getFusionComponentTypes(), [
            'response' => HttpResponseInterface::class,
        ]);
    }

    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'router' => Router::class,
            'request' => HttpRequest::class,
            'response' => HttpResponseInterface::class,
            'cookie' => Cookie::class,
            'user' => UserInterface::class,
        ]);
    }

	public function run(): void
	{
        try {
            $this->events->trigger(self::EVENT_BEFORE_RUN);

            $this->authenticateUser();

            $request = $this->request;
            $response = $this->response;
            $this->events->trigger(self::EVENT_BEFORE_HANDLE_REQUEST, $request);
            $requestHandler = RequestHandler::create($request, $response);
            $requestHandler->handle();

            if (!$this->user || $this->user->isGuest()) {
                header('lx-user-status: guest');
            }

            $this->events->trigger(self::EVENT_BEFORE_SEND_RESPONSE, $response);
            $response->send();
            $this->events->trigger(self::EVENT_AFTER_SEND_RESPONSE, $response);

            $this->events->trigger(self::EVENT_AFTER_RUN);
        } catch (\Throwable $exception) {
            //TODO обработчик ошибок должен быть отдельно - какой-то класс/компонент?
            if ($this->isProd()) {
                $this->logger->error($exception, [
                    'URL' => $this->request->getUrl(),
                ]);
            } else {
                $errorString = ErrorHelper::renderErrorString($exception, [
                    'URL' => $this->request->getUrl(),
                ]);
                if ($this->request->isPageLoad()) {
                    /** @var HtmlRendererInterface $renderer */
                    $renderer = $this->diProcessor->createByInterface(HtmlRendererInterface::class);
                    $result = $renderer
                        ->setTemplateType(500)
                        ->setParams([
                            'error' => $errorString
                        ])->render();
                    /** @var HttpResponseInterface $response */
                    $response = $this->diProcessor->createByInterface(HttpResponseInterface::class, [
                        [
                            'code' => HttpResponse::SERVER_ERROR,
                            'data' => $result,
                        ]
                    ]);
                } else {
                    \lx::dump($errorString);
                    $response = $this->diProcessor->createByInterface(HttpResponseInterface::class, [
                        [
                            'code' => HttpResponse::SERVER_ERROR,
                            'data' => '',
                        ]
                    ]);
                }
                $response->send();
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
