<?php

namespace lx;

/**
 * Class ConfigHelper
 * @package lx
 */
class ConfigHelper
{
	/**
	 * @param array $commonConfig
	 * @param array $config
	 */
	public static function prepareServiceConfig($commonConfig, &$config)
	{
		self::prepareConfig($commonConfig, $config['service']);

		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$config['service']['aliases'] += $value;
			} elseif ( ! array_key_exists($key, $config['service'])) {
				$config['service'][$key] = $value;
			}
		}
	}

	/**
	 * @param array $commonConfig
	 * @param array $config
	 */
	public static function preparePluginConfig($commonConfig, &$config)
	{
		self::prepareConfig($commonConfig, $config);

		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$config['aliases'] += $value;
			} elseif (!array_key_exists($key, $config)) {
				$config[$key] = $value;
			}
		}
	}

	/**
	 * @param string $name
	 * @param array $allInjections
	 * @param array $config
	 */
	public static function serviceInject($name, $allInjections, &$config)
	{
		if (!is_array($allInjections) || !array_key_exists($name, $allInjections)) {
			return;
		}

		$injections = $allInjections[$name];
		$config['service'] = ArrayHelper::mergeRecursiveDistinct($config['service'], $injections, true);
	}

	/**
	 * @param string $name
	 * @param string $prototype
	 * @param array $injections
	 * @param array $config
	 */
	public static function pluginInject($name, $prototype, $injections, &$config)
	{
		if ($injections) {
			if (array_key_exists($prototype, $injections)) {
				$config = ArrayHelper::mergeRecursiveDistinct(
					$config,
					$injections[$prototype],
					true
				);
			}

			if (array_key_exists($name, $injections)) {
				$config = ArrayHelper::mergeRecursiveDistinct(
					$config,
					$injections[$name],
					true
				);
			}
		}
	}

	/**
	 * @param array $commonConfig
	 * @param array $config
	 */
	private static function prepareConfig(&$commonConfig, &$config)
	{
		$useAliases = isset($commonConfig['useCommonAliases'])
			? $commonConfig['useCommonAliases']
			: false;
		unset($commonConfig['useCommonAliases']);
		if ($useAliases) {
			if ( ! isset($config['aliases'])) {
				$config['aliases'] = [];
			}
		} else {
			unset($commonConfig['aliases']);
		}
	}
}
