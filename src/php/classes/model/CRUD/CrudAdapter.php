<?php

namespace lx;

abstract class CrudAdapter {
	protected $modelProvider;

	public function setModelProvider($modelProvider) {
		$this->modelProvider = $modelProvider;
	}

	abstract public function loadModelsData($schema, $condition);
	abstract public function saveModel($model);
	abstract public function deleteModel($model);
	abstract public function saveModels($schema, $arr);
	abstract public function deleteModels($schema, $arr);

	abstract public function checkNeedTable($schema);

	abstract public function correctModel($modelName, $tableName, $actions);
	abstract public function addModelEssences($modelName, $tableName, $actions);
}
