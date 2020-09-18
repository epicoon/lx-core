<?php

namespace lx;

/**
 * Class AbstractResourceVoter
 * @package lx
 */
abstract class AbstractResourceVoter implements ResourceVoterInterface
{
    use ObjectTrait;
	use ApplicationToolTrait;

	/** @var Resource */
	protected $owner;

	/**
	 * @return Resource
	 */
	public function getResource()
	{
		return $this->owner;
	}

	/**
	 * @param Resource $resource
	 */
	public function setResource(Resource $resource)
	{
		$this->owner = $resource;
	}

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return array
	 */
	public function processActionParams(User $user, $actionName, $params)
	{
		return $params;
	}

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return bool
	 */
	abstract public function run(User $user, $actionName, $params);
}
