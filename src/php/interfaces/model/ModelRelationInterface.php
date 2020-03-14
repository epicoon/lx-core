<?php

namespace lx;

/**
 * Interface ModelRelationInterface
 * @package lx
 */
interface ModelRelationInterface
{
	/**
	 * @return int
	 */
	public function getType();

	/**
	 * @param int $type
	 * @return bool
	 */
	public function typeIs($type);

	/**
	 * @return string
	 */
	public function getRelativeModelName();

	/**
	 * @return ArrayInterface|array
	 */
	public function getRelativeRelationNames();

	/**
	 * @return ArrayInterface|array
	 */
	public function getRelativeRelations();

	/**
	 * @param string $name
	 * @return ModelRelationInterface
	 */
	public function getRelativeRelation($name = null);
}
