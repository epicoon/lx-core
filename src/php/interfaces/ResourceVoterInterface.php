<?php

namespace lx;

/**
 * Interface ResourceVoterInterface
 * @package lx
 */
interface ResourceVoterInterface
{
	/**
	 * @param Resource $resource
	 */
	public function setResource(Resource $resource);

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return array
	 */
	public function processActionParams(User $user, $actionName, $params);

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return bool
	 */
	public function run(User $user, $actionName, $params);
}
