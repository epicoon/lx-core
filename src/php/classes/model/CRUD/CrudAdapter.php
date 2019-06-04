<?php

namespace lx;

abstract class CrudAdapter {
	protected $modelProvider;

	public function __construct($params = []) {

	}

	public function setModelProvider($modelProvider) {
		$this->modelProvider = $modelProvider;
	}

	abstract public function loadModel($modelName, $condition);
	abstract public function saveModel($model);
	abstract public function deleteModel($model);

	abstract public function loadModels($modelName, $condition = null);
	abstract public function saveModels($arr);
	abstract public function deleteModels($arr);

	abstract public function checkNeedTable($modelName);
	abstract public function createTable($modelName, $schema = null);
	abstract public function deleteTable($modelName);

	abstract public function checkNeedRelationTable($modelName, $relativeModelName);
	abstract public function createRelationTable($modelName, $relativeModelName);
	abstract public function addRelations($model, $relation, $modelsList);
	abstract public function delRelations($model, $relation, $modelsList);
	abstract public function loadRelations($model, $relation);

	abstract public function correctModel($modelName, $actions);
	abstract public function correctModelEssences($modelName, &$actions, $schema = null);
}
