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
			$dir = new Directory( \lx::$conductor->getSystemPath('lxWidgets') );
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

				$path = \lx::$conductor->getFullPath($path);
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
		if ($namespace == 'lx') return \lx::$conductor->getSystemPath('lxWidgets');

		//todo!!!!!!!!!!!! итого работает только для lx-виджетов. Этот метод надо упразднить
		return null;
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
		$path = Autoloader::getInstance()->getClassPath($namespace . '\\' . $name);

		//todo закостылено, _main захардкожен!
		if ($path) {
			$path = preg_replace('/\.php$/', '.js', $path);
			if (!file_exists($path)) {
				$path = false;
			}
		} else {
			$path = self::getDirectoryPath($namespace, $name) . '/_main.js';
		}

		return $path;
	}

	/**
	 * Путь к php-файлу конкретного виджета
	 * */
	public static function getPhpFilePath($namespace, $name) {
		$path = Autoloader::getInstance()->getClassPath($namespace . '\\' . $name);

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
			$path = self::getJsFilePath(str_replace('.', '\\', $namespace), $names[0]);

			$widgetsPath = dirname($path, 2);
			$dummyPath = $widgetsPath . '/dummy';

			$codes[$namespace] = [
				'path' => $dummyPath,
				'code' => []
			];

			foreach ($names as $name) {
				$dirPath = $widgetsPath . '/' . $name . '/';
				$fileName = null;
				if (file_exists($dirPath . '_main.js')) {
					$fileName = '_main.js';
				} elseif (file_exists($dirPath . '_' . $name . '.js')) {
					$fileName = '_' . $name . '.js';
				} elseif (file_exists($dirPath . $name . '.js')) {
					$fileName = $name . '.js';
				}
				if ($fileName === null) {
					continue;
				}

				$codes[$namespace]['code'][] = $name . ':' . $fileName;
			}
			$codes[$namespace]['code'] = '#lx:require {' . implode(',', $codes[$namespace]['code']) . '};';
		}

		foreach ($codes as $namespace => $data) {
			$codes[$namespace] = JsCompiler::compileCode($data['code'], $data['path']);
		}

		return implode('', $codes);
	}
}
