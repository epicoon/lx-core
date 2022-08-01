<?php

namespace lx;

use lx;

class Resource implements ResourceInterface, ObjectInterface
{
    use ObjectTrait;

	protected ?ResourceVoterInterface $voter = null;

    protected function init(): void
	{
		if ($this->voter) {
			$this->voter->setResource($this);
		}
	}

	public static function getDependenciesConfig(): array
	{
		return [
			'voter' => ResourceVoterInterface::class,
		];
	}

    /**
     * @param mixed $data
     */
	public function prepareResponse($data): HttpResponseInterface
    {
        return $this->newResponse($data);
    }
    
    public function prepareWarningResponse($data = []): HttpResponseInterface
    {
        $responce = $this->newResponse($data);
        $responce->setWarning();
        return $responce;
    }

    /**
     * @param array|string $error
     */
    public function prepareErrorResponse($error, int $code = HttpResponse::BAD_REQUEST): HttpResponseInterface
    {
        return $this->newResponse($error, $code);
    }

	public function run(array $params, ?UserInterface $user = null): HttpResponseInterface
	{
	    return $this->newResponse('Resource not found', HttpResponse::NOT_FOUND);
	}

	public function runAction(string $actionName, array $params, ?UserInterface $user = null): ?HttpResponseInterface
	{
		if (!method_exists($this, $actionName)) {
			return $this->newResponse('Resource not found', HttpResponse::NOT_FOUND);
		}

		$list = static::getActionMethodsList();
		if (is_array($list) && array_search($actionName, $list) === false) {
			return $this->newResponse('Resource not found',HttpResponse::NOT_FOUND);
		}

		$list = static::getOwnMethodsList();
		if (array_search($actionName, $list) !== false) {
			return $this->newResponse('Resource not found',HttpResponse::NOT_FOUND);
		}

		if (preg_match('/^__/', $actionName)) {
			return $this->newResponse('Resource not found',HttpResponse::NOT_FOUND);
		}

		$re = new \ReflectionMethod($this, $actionName);
		if ($re->isStatic() || !$re->isPublic()) {
			return $this->newResponse('Resource not found',HttpResponse::NOT_FOUND);
		}

		if ($user === null) {
			$user = lx::$app->user;
		}

		if ($user && $this->voter) {
			if (!$this->voter->run($user, $actionName, $params)) {
                if ($user->isGuest()) {
                    return $this->newResponse('Unauthorized', HttpResponse::UNAUTHORIZED);
                }

				return $this->newResponse('Forbidden',HttpResponse::FORBIDDEN);
			}

			$params = $this->voter->processActionParams($user, $actionName, $params);
		}

		if ($actionName == 'run') {
			return $this->run($params, $user);
		}

		return $params
			? call_user_func_array([$this, $actionName], $params)
			: $this->$actionName();
	}

    public function beforeAction(): void
    {
        // pass
    }

    public function beforeSuccessfulAction(): void
    {
        // pass
    }

    public function beforeFailedAction(): void
    {
        // pass
    }

    public function afterSuccessfulAction(): void
    {
        // pass
    }

    public function afterFailedAction(): void
    {
        // pass
    }

    public function afterAction(): void
    {
        // pass
    }
	
	protected static function getOwnMethodsList(): array
	{
		return [
		    'runAction',
            'prepareResponse',
            'prepareErrorResponse',
            'beforeAction',
            'beforeSuccessfulAction',
            'beforeFailedAction',
            'afterSuccessfulAction',
            'afterFailedAction',
            'afterAction',
        ];
	}

	protected static function getActionMethodsList(): ?array
	{
		return null;
	}

    /**
     * @param mixed $data
     */
    protected function newResponse($data, int $code = HttpResponse::OK): HttpResponseInterface
    {
        return lx::$app->diProcessor->createByInterface(HttpResponseInterface::class, [
            [
                'code' => $code,
                'data' => $data,
            ]
        ]);
    }
}
