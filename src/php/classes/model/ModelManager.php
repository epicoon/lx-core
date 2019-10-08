<?php

namespace lx;

/**
 * Class ModelManager
 * @package lx
 */
class ModelManager extends ApplicationTool
{
	/** @var CrudAdapter */
	private $crudTool = null;
	/** @var ModelSchema */
	private $schema;

	/**
	 * ModelManager constructor.
	 * @param $app AbstractApplication
	 * @param $crudTool CrudAdapter
	 * @param $schema ModelSchema
	 */
	public function __construct($app, $crudTool, $schema)
	{
		parent::__construct($app);
		$this->crudTool = $crudTool;
		$this->schema = $schema;
	}


	/**************************************************************************************************************************
	 * CRUD действия с одной моделью
	 *************************************************************************************************************************/

	/**
	 * @return array|ModelData
	 */
	public function newModel()
	{
		return ModelData::getNew($this, 1);
	}

	/**
	 * @param $condition array|mixed
	 * @return ModelData
	 * @throws \Exception
	 */
	public function loadModel($condition)
	{
		$this->checkCrud();
		return $this->crudTool->loadModel($this->schema->getName(), $condition);
	}

	/**
	 * @param $model ModelData
	 * @throws \Exception
	 */
	public function saveModel($model)
	{
		$this->checkCrud();
		$this->crudTool->saveModel($model);
	}

	/**
	 * @param $model ModelData
	 * @throws \Exception
	 */
	public function deleteModel($model)
	{
		if ($model->pk() === null) return;

		$this->checkCrud();
		$this->crudTool->deleteModel($model);

		$model->setPk(null);
	}

	/**
	 * @param $model ModelData
	 * @param $relation ModelFieldRelation
	 * @param $modelsList array
	 * @throws \Exception
	 */
	public function addRelations($model, $relation, $modelsList)
	{
		$this->checkCrud();
		$this->crudTool->addRelations($model, $relation, $modelsList);
	}

	/**
	 * @param $model ModelData
	 * @param $relation ModelFieldRelation
	 * @return array
	 * @throws \Exception
	 */
	public function loadRelations($model, $relation)
	{
		$this->checkCrud();
		return $this->crudTool->loadRelations($model, $relation);
	}

	/**
	 * @param $model ModelData
	 * @param $relation ModelFieldRelation
	 * @param $modelsList array
	 * @throws \Exception
	 */
	public function delRelations($model, $relation, $modelsList)
	{
		$this->checkCrud();
		$this->crudTool->delRelations($model, $relation, $modelsList);
	}


	/**************************************************************************************************************************
	 * CRUD действия с множествами моделей
	 *************************************************************************************************************************/

	/**
	 * Создание нескольких новых моделей
	 * @param $count int - сколько моделей создать
	 * @return array|ModelData
	 */
	public function newModels($count)
	{
		return ModelData::getNew($this, $count);
	}

	/**
	 * Загрузка нескольких моделей
	 * @param $condition null|array
	 * @return array
	 * @throws \Exception
	 */
	public function loadModels($condition = null)
	{
		$this->checkCrud();
		return $this->crudTool->loadModels($this->schema->getName(), $condition);
	}

	/**
	 * @param $arr array
	 * @throws \Exception
	 */
	public function saveModels($arr)
	{
		$this->checkCrud();
		$this->crudTool->saveModels($arr);
	}

	/**
	 * @param $arr array
	 * @throws \Exception
	 */
	public function deleteModels($arr)
	{
		$this->checkCrud();
		$this->crudTool->deleteModels($arr);
	}


	/**************************************************************************************************************************
	 * Всякое прочее
	 *************************************************************************************************************************/

	/**
	 * @return string
	 */
	public function getModelName()
	{
		return $this->schema->getName();
	}

	/**
	 * @return ModelSchema
	 */
	public function getSchema()
	{
		return $this->schema;
	}

	/**
	 * @return CrudAdapter
	 */
	public function getCrudAdapter()
	{
		return $this->crudTool;
	}

	/**
	 * @return Service
	 */
	public function getService()
	{
		return $this->crudTool->getService();
	}

//	/**
//	 * TODO идея для кэширования, пока нет такого
//	 */
//	public function resetModels()
//	{
//		$this->models = [];
//	}

	/**
	 * @throws \Exception
	 */
	private function checkCrud()
	{
		if ($this->crudTool === null) {  //todo какой-нибудь интерфейс еще проверять
			throw new \Exception('ModelManager CRUD operation failed. There is no CRUD tool.', 400);
		}
	}
}
