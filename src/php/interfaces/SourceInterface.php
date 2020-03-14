<?php

namespace lx;

/**
 * Interface SourceInterface
 * @package lx
 */
interface SourceInterface
{
	/**
	 * @param array $params
	 * @param User $user
	 * @return mixed|SourceError
	 */
	public function run($params, $user = null);

	/**
	 * @param string $actionName
	 * @param array $params
	 * @param User $user
	 * @return mixed|SourceError
	 */
	public function runAction($actionName, $params, $user = null);
}
