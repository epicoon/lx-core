<?php

namespace lx;

/**
 * @group {i18n:widgets}
 * */
class Rect {
	use ApplicationToolTrait;

	/**
	 * Возвращает массив имен методов, к которым можно напрямую обращаться по ajax
	 * @return array
	 */
	protected static function ajaxMethods() {
		return [];
	}

	/**
	 * Возвращает имя метода, который нужно выполнить по его ключу.
	 * Можно переопределить у потомков, сопоставить методам ключи, добавить проверки и т.п.
	 * @param $methodKey
	 * @return bool
	 */
	public static function ajaxRoute($methodKey) {
		if (in_array($methodKey, static::ajaxMethods())) {
			return $methodKey;
		}

		return false;
	}
}
