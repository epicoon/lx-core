<?php

namespace lx;

/**
 * Interface SourceVoterInterface
 * @package lx
 */
interface SourceVoterInterface
{
	/**
	 * @param Source $source
	 * @return mixed
	 */
	public function setSource(Source $source);

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return bool
	 */
	public function run(User $user, $actionName, $params);

	/**
	 * @param User $user
	 * @param string $actionName
	 * @param array $params
	 * @return array
	 */
	public function processActionParams(User $user, $actionName, $params);
}
