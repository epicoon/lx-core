<?php

namespace lx;

/**
 * Для классов, объявленных в сервисах описание набора методов доступа к возможностям сервиса
 * */
interface ClassOfServiceInterface {
	/**
	 * Получить имя сервиса для текущего класса
	 *
	 * @return string|null
	 * */
	public function getServiceName();

	/**
	 * Получить сервис для текущего класса
	 *
	 * @return lx\Service|null
	 * */
	public function getService();
}
