<?php

namespace lx;

/**
 * Class Source
 * @package lx
 */
class Source extends Object  //TODO SourceInterface
{
	use ApplicationToolTrait;

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
	 * @return array|null
	 */
	public static function getActionMethodsList()
	{
		return null;
	}

	/**
	 * @param string $actionName
	 * @param array $params
	 * @param User $user
	 * @return bool|mixed
	 */
	public function runAction($actionName, $params, $user = null)
	{
		$list = static::getActionMethodsList();
		if (is_array($list) && !array_key_exists($actionName, $list)) {
			return new SourceError(ResponseCodeEnum::NOT_FOUND);
		}

		if ($user === null) {
			$user = $this->app->user;
		}

		if ($user && $this->voter) {
			if ( ! $this->voter->run($user, $actionName, $params)) {
				return new SourceError(ResponseCodeEnum::FORBIDDEN);
			}

			$params = $this->voter->processActionParams($user, $actionName, $params);
		}

		return $params
			? call_user_func_array([$this, $actionName], $params)
			: $this->$actionName();
	}
}
