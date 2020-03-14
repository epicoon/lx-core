<?php

namespace lx;

/**
 * Class Source
 * @package lx
 */
class Source extends BaseObject implements SourceInterface
{
	use ApplicationToolTrait;

	/** @var SourceVoterInterface */
	private $voter;

	/**
	 * Source constructor.
	 * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->voter = $config['voter'] ?? null;
		if ($this->voter) {
			$this->voter->setSource($this);
		}
	}

	/**
	 * @return array
	 */
	public static function getConfigProtocol()
	{
		return [
			'voter' => SourceVoterInterface::class,
		];
	}

	/**
	 * @param array $params
	 * @param User $user
	 * @return mixed|SourceError
	 */
	public function run($params, $user = null)
	{
		return new SourceError(ResponseCodeEnum::NOT_FOUND);
	}

	/**
	 * @param string $actionName
	 * @param array $params
	 * @param User $user
	 * @return mixed|SourceError
	 */
	public function runAction($actionName, $params, $user = null)
	{
		if (!method_exists($this, $actionName)) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$list = static::getActionMethodsList();
		if (is_array($list) && array_search($actionName, $list) === false) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$list = static::getOwnMethodsList();
		if (array_search($actionName, $list) !== false) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		if (preg_match('/^__/', $actionName)) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		$re = new \ReflectionMethod($this, $actionName);
		if ($re->isStatic() || !$re->isPublic()) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		if ($user === null) {
			$user = $this->app->user;
		}

		if ($user && $this->voter) {
			if (!$this->voter->run($user, $actionName, $params)) {
				return new SourceError(ResponseCodeEnum::FORBIDDEN);
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
		return ['runAction'];
	}

	/**
	 * @return array|null
	 */
	protected static function getActionMethodsList()
	{
		return null;
	}
}
