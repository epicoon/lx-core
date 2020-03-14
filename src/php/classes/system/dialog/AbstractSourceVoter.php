<?php

namespace lx;

/**
 * Class AbstractSourceVoter
 * @package lx
 */
abstract class AbstractSourceVoter extends BaseObject implements SourceVoterInterface
{
	use ApplicationToolTrait;

	/** @var Source */
	protected $owner;

	/**
	 * @return Source
	 */
	public function getSource()
	{
		return $this->owner;
	}

	/**
	 * @param Source $source
	 */
	public function setSource(Source $source)
	{
		$this->owner = $source;
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
