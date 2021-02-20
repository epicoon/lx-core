<?php

namespace lx;

/**
 * Class Resource
 * @package lx
 */
class Resource implements ResourceInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;
	use ErrorCollectorTrait;

	/** @var ResourceVoterInterface */
	private $voter;

	/**
	 * Resource constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
	    $this->__objectConstruct($config);

		$this->voter = $config['voter'] ?? null;
		if ($this->voter) {
			$this->voter->setResource($this);
		}
	}

	public static function getConfigProtocol(): array
	{
		return [
			'voter' => ResourceVoterInterface::class,
		];
	}

    /**
     * @param mixed $data
     * @return ResponseInterface
     */
	public function prepareResponse($data)
    {
        return $this->newResponse($data);
    }

    /**
     * @param array|string $error
     * @param int $code
     * @return ResponseInterface
     */
    public function prepareErrorResponse($error, $code = ResponseCodeEnum::BAD_REQUEST_ERROR)
    {
        return $this->newResponse($error, $code);
    }

	/**
	 * @param array $params
	 * @param User $user
	 * @return ResponseInterface
	 */
	public function run($params, $user = null)
	{
	    return $this->newResponse('Resource not found', ResponseCodeEnum::NOT_FOUND);
	}

	/**
	 * @param string $actionName
	 * @param array $params
	 * @param User $user
	 * @return ResponseInterface
	 */
	public function runAction($actionName, $params, $user = null)
	{
		if (!method_exists($this, $actionName)) {
			return $this->newResponse('Resource not found', ResponseCodeEnum::NOT_FOUND);
		}

		$list = static::getActionMethodsList();
		if (is_array($list) && array_search($actionName, $list) === false) {
			return $this->newResponse('Resource not found',ResponseCodeEnum::NOT_FOUND);
		}

		$list = static::getOwnMethodsList();
		if (array_search($actionName, $list) !== false) {
			return $this->newResponse('Resource not found',ResponseCodeEnum::NOT_FOUND);
		}

		if (preg_match('/^__/', $actionName)) {
			return $this->newResponse('Resource not found',ResponseCodeEnum::NOT_FOUND);
		}

		$re = new \ReflectionMethod($this, $actionName);
		if ($re->isStatic() || !$re->isPublic()) {
			return $this->newResponse('Resource not found',ResponseCodeEnum::NOT_FOUND);
		}

		if ($user === null) {
			$user = $this->app->user;
		}

		if ($user && $this->voter) {
			if (!$this->voter->run($user, $actionName, $params)) {
				return $this->newResponse('Resource is unavailable',ResponseCodeEnum::FORBIDDEN);
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

	/**
	 * @return array
	 */
	protected static function getOwnMethodsList()
	{
		return ['runAction', 'prepareResponse', 'prepareErrorResponse'];
	}

	/**
	 * @return array|null
	 */
	protected static function getActionMethodsList()
	{
		return null;
	}

    /**
     * @param mixed $data
     * @param int $code
     * @return ResponseInterface
     */
    protected function newResponse($data, $code = ResponseCodeEnum::OK)
    {
        return $this->app->diProcessor->createByInterface(ResponseInterface::class, [$data, $code]);
    }
}
