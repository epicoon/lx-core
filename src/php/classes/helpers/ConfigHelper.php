<?php

namespace lx;

use lx;

/**
 * Class ConfigHelper
 * @package lx
 */
class ConfigHelper
{
	/**
	 * @param string $serviceName
	 * @param array $config
     * @return array
	 */
	public static function prepareServiceConfig($serviceName, $config)
	{
	    $result = [];
	    if ($config['autoload']['psr-4'] ?? null) {
	        $result['autoload.psr-4'] = $config['autoload']['psr-4'];
        }

	    $result = array_merge($result, $config['service']);

        $commonConfig = lx::$app->getDefaultServiceConfig();
		self::prepareConfig($commonConfig, $result);

		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$result['aliases'] += $value;
			} elseif ( ! array_key_exists($key, $result)) {
				$result[$key] = $value;
			}
		}

		$result = self::injectServiceConfig($serviceName, $result);

        return $result;
	}

    /**
     * @param string $name
     * @param array $config
     * @return array
     */
    public static function injectServiceConfig($name, $config)
    {
        $allInjections = lx::$app->getConfig('configInjection');

        if (!is_array($allInjections) || !array_key_exists($name, $allInjections)) {
            return $config;
        }

        $injections = $allInjections[$name];
        return ArrayHelper::mergeRecursiveDistinct($config, $injections, true);
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

		$commonComponents = $commonConfig['components'] ?? null;
		$components = $config['components'] ?? $commonComponents;
		if ($components) {
		    $components = ArrayHelper::mergeRecursiveDistinct($components, $commonComponents ?? []);
		    $config['components'] = $components;
        }
	}
}
