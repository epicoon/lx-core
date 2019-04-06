<?php

namespace lx;

class ConfigHelper {
	public static function prepareServiceConfig($commonConfig, &$config) {
		self::prepareConfig($commonConfig, $config['service']);

		// Использование дефолтных настройки
		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$config['service']['aliases'] += $value;
			} elseif (!array_key_exists($key, $config['service'])) {
				$config['service'][$key] = $value;
			}
		}
	}

	public static function prepareModuleConfig($commonConfig, &$config) {
		self::prepareConfig($commonConfig, $config);

		// Использование дефолтных настройки
		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$config['aliases'] += $value;
			} elseif (!array_key_exists($key, $config)) {
				$config[$key] = $value;
			}
		}
	}

	public static function serviceInject($name, $injections, &$config) {
		if ($injections) {
			if (array_key_exists($name, $injections)) {
				$configInjection = $injections[$name];
				foreach ($configInjection as $key => $value) {
					$config['service'][$key] = $value;
				}
			}
		}
	}

	public static function moduleInject($name, $prototype, $injections, &$config) {
		if ($injections) {
			if (array_key_exists($prototype, $injections)) {
				$configInjection = $injections[$prototype];
				foreach ($configInjection as $key => $value) {
					$config[$key] = $value;
				}
			}
			
			if (array_key_exists($name, $injections)) {
				$configInjection = $injections[$name];
				foreach ($configInjection as $key => $value) {
					$config[$key] = $value;
				}
			}
		}
	}

	private static function prepareConfig(&$commonConfig, &$config) {
		$useAliases = isset($commonConfig['useCommonAliases'])
			? $commonConfig['useCommonAliases']
			: false;
		unset($commonConfig['useCommonAliases']);
		if ($useAliases) {
			if (!isset($config['aliases'])) $config['aliases'] = [];
		} else {
			unset($commonConfig['aliases']);
		}
	}
}
