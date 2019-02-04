<?php

namespace lx;

class ModelBrowser {
	private $service;

	public function __construct($service) {
		$this->service = $service;
	}


	/**
	 * Получить аналитическую информацию по всем моделям
	 * @param array $map - карта необходимых параметров. Если не передана, возвращаются все параметры
	 * Возможные параметры: [path, code, needTable, hasChanges]
	 * @return array - возвращает ассоциативный массив: ключи - имена моделей, значения - запрошенные данные о моделях
	 * */
	public function getModelsInfo($map = null) {
		if ($map === null) {
			$map = ['path', 'code', 'needTable', 'hasChanges'];
		}
		$pathes = $this->service->modelProvider->getSchemasPath();

		$result = [];
		foreach ($pathes as $modelName => $path) {
			$file = new File($path);
			$code = $file->get();

			$data = [];
			if (array_search('path', $map) !== false) {
				$data['path'] = $path;
			}
			if (array_search('code', $map) !== false) {
				$data['code'] = $code;
			}
			if (array_search('needTable', $map) !== false) {
				$data['needTable'] = $this->checkModelNeedTable($modelName);
			}
			if (array_search('hasChanges', $map) !== false) {
				$data['hasChanges'] = $this->checkModelNeedChange(['code' => $code]);
			}

			$result[$modelName] = $data;
		}

		return $result;
	}


	/**
	 * Определяет нужно ли создать таблицу для модели
	 * */
	public function checkModelNeedTable($modelName) {
		return $this->service->modelProvider->checkModelNeedTable($modelName);
	}

	/**
	 * Определяет есть ли директивы для изменений в коде модели
	 * $modelInfo может содержать путь, файл, код
	 * */
	public function checkModelNeedChange($modelInfo) {
		// Можно передать имя модели
		if (is_string($modelInfo)) {
			$modelInfo = ['path' => $this->service->conductor->getModelPath($modelInfo)];
		}

		$code = '';
		if (array_key_exists('code', $modelInfo)) {
			$code = $modelInfo['code'];
		} elseif (array_key_exists('file', $modelInfo)) {
			$code = $modelInfo['file']->get();
		} elseif (array_key_exists('path', $modelInfo)) {
			$code = (new File($modelInfo['path']))->get();
		}

		/*
		!!change : можно поменять
			- имя таблицы
			- имя поля
			- тип поля
			- дефолтное значение поля
		!!add : можно добавить
			- новое поле (?)
			- новые экземпляры моделей в формате
				!!add:
				  - f0: val1_0
				    f1: val1_1
				  - f0: val2_0
				    f1: val2_1
				или
				!!add:
				  vars:
				    $slug1: SomeModel('slug1').field
				    $slug2: SomeModel('slug2').field + '_' + SomeModel('slug1').field
				  models:
				    - f0: $slug1
				      f1: val1_1
				    - f0: $slug2
				      f1: val2_1
		!!addTable : можно добавить
			- новые экземпляры моделей в формате
				!!addTable: [
				  [ f0,     f1     ],
				  [ val1_0, val1_1 ],
				  [ val2_0, val2_1 ]
				]
				или
				!!add:
				  vars:
				    $slug1: SomeModel('slug1')->field;
				    $slug2: SomeModel('slug2')->field . '_' . SomeModel('slug1')->field;
				  models: [
				    [ f0,     f1     ],
				    [ $slug1, val1_1 ],
				    [ $slug2, val2_1 ]
				  ]
		!!remove : можно удалить
			- поле
			- экземпляры моделей по значениям полей
		!!query : выполнение запроса (видимо, с синтаксисом SQL)
		*/
		return (bool)preg_match('/!!(change|add|remove|query)/', $code);
	}
}
