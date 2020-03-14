<?php

namespace lx;

/**
 * Interface ModelManagerInterface
 * @package lx
 */
interface ModelManagerInterface
{
	/**************************************************************************************************************************
	 * CRUD actions this single model
	 *************************************************************************************************************************/

	/**
	 * @return int
	 */
	public function getModelsCount();

	/**
	 * @return ModelInterface
	 */
	public function newModel();

	/**
	 * @param array|mixed $condition
	 * @return ModelInterface
	 */
	public function loadModel($condition);

	/**
	 * @param ModelInterface $model
	 */
	public function saveModel($model);

	/**
	 * @param ModelInterface $model
	 */
	public function deleteModel($model);

	/**
	 * @param ModelInterface $model
	 * @param ModelRelationInterface $relation
	 * @param ArrayInterface|array $modelsList
	 * @param string $relRelationName
	 */
	public function addRelations($model, $relation, $modelsList, $relRelationName = null);

	/**
	 * @param ModelInterface $model
	 * @param ModelRelationInterface $relation
	 * @return ArrayInterface
	 */
	public function loadRelations($model, $relation);

	/**
	 * @param $model ModelInterface
	 * @param ModelRelationInterface $relation
	 * @param ArrayInterface|array $modelsList
	 * @param string $relationName
	 */
	public function removeRelations($model, $relation, $modelsList, $relationName = null);

	/**
	 * @param ModelInterface $model
	 * @param ModelRelationInterface $relation
	 */
	public function removeAllRelations($model, $relation);


	/**************************************************************************************************************************
	 * CRUD actions with multiple models
	 *************************************************************************************************************************/

	/**
	 * @param int $count
	 * @return ArrayInterface|array
	 */
	public function newModels($count);

	/**
	 * @param array|mixed $condition
	 * @return ArrayInterface
	 */
	public function loadModels($condition = null);

	/**
	 * @param ArrayInterface|array $arr
	 */
	public function saveModels($arr);

	/**
	 * @param ArrayInterface|array $arr
	 */
	public function deleteModels($arr);

	/**
	 * @param array|mixed $condition
	 */
	public function deleteModelsByCondition($condition);
}
