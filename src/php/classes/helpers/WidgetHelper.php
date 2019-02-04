<?php

namespace lx;

class WidgetHelper {
	private static $lxWidgets = null;
	private static $clientWidgets = null;

	/**
	 * Имена всех виджетов встроенных в платформу
	 * */
	public static function getLxWidgetNames() {
		if (self::$lxWidgets === null) {
			$dir = new Directory( \lx::$conductor->lxWidgets );
			self::$lxWidgets = $dir->getContent(['findType' => Directory::FIND_NAME])->getData();
		}

		return self::$lxWidgets;
	}

	/**
	 * Имена всех виджетов во всех пространствах имен клиентского кода
	 * */
	public static function getClientWidgetNames() {
		//todo!!!!!!!!!!!!!!!!!!!!!!!!!
		return [];

		if (self::$clientWidgets === null) {
			self::$clientWidgets = [];
			$widgets = \lx::getConfig('widgets');
			if (!$widgets) return [];

			foreach ($widgets as $namespace => $path) {
				self::$clientWidgets[$namespace] = [];

				$path = \lx::$conductor->decodeAlias($path);
				$dir = new Directory($path);
				self::$clientWidgets[$namespace] = $dir->getContent(['findType' => Directory::FIND_NAME])->getData();
			}
		}

		return self::$clientWidgets;
	}

	/**
	 * Вернет путь к директории с виджетами конкретного пространства имен
	 * */
	public static function getNamespacePath($namespace) {
		if ($namespace == 'lx') return \lx::$conductor->lxWidgets;

		$widgets = \lx::getConfig('widgets');
		if (!$widgets || !array_key_exists($namespace, $widgets)) return false;
		return \lx::$conductor->decodeAlias($widgets[$namespace]);
	}

	/**
	 * Путь к директории конкретного виджета
	 * */
	public static function getDirectoryPath($namespace, $name) {
		$namespacePath = self::getNamespacePath($namespace);
		if (!$namespacePath) return false;

		return $namespacePath . '/' . $name;
	}

	/**
	 * Путь к js-файлу конкретного виджета
	 * */
	public static function getJsFilePath($namespace, $name) {
		$directoryPath = self::getDirectoryPath($namespace, $name);
		if (!$directoryPath) return false;

		return $directoryPath . '/_main.js';
	}

	/**
	 * Путь к php-файлу конкретного виджета
	 * */
	public static function getPhpFilePath($namespace, $name) {
		$directoryPath = self::getDirectoryPath($namespace, $name);
		if (!$directoryPath) return false;

		return $directoryPath . '/_main.php';
	}

	/**
	 * Собирает код для виджетов согласно переданной карте с учетом пространств имен
	 * */
	public static function getWidgetsCode($list) {
		$codes = [];
		foreach ($list as $namespace => $names) {
			$codes[$namespace] = [
				'path' => self::getNamespacePath($namespace) . '/temp',
				'code' => []
			];

			foreach ($names as $key => $value) {
				$name = is_string($key) ? $key : $value;
				$codes[$namespace]['code'][] = $name . ':_main.js';
			}
			$codes[$namespace]['code'] = '#require {' . implode(',', $codes[$namespace]['code']) . '};';
		}
		foreach ($codes as $namespace => $data) {
			$codes[$namespace] = JsCompiler::compileCode($data['code'], $data['path']);
		}

		return implode('', $codes);
	}
}
