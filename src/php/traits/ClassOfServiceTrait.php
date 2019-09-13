<?php

namespace lx;

/**
 * Для классов, объявленных в сервисах реализация набора методов доступа к возможностям сервиса
 * */
trait ClassOfServiceTrait {
	/**
	 * Получить имя сервиса для текущего класса
	 *
	 * @return string|null
	 * */
	public function getServiceName() {
		return ClassHelper::defineService(static::class);
	}

	/**
	 * Получить сервис для текущего класса
	 *
	 * @return lx\Service|null
	 * */
	public function getService() {
		$name = $this->getServiceName();
		if (!$name) {
			return null;
		}

		return \lx::$app->getService($name);
	}

	/**
	 * Получить менеджер моделей сервиса для текущего класса
	 *
	 * @param $modelName string
	 * @return lx\ModelManager|null
	 * */
	public function getModelManager($modelName) {
		$service = $this->getService();
		if (!$service) {
			return null;
		}

		return $service->getModelManager($modelName);
	}
}
