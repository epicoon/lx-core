<?php

namespace lx;

use lx;

class ConfigHelper
{
	public static function prepareServiceConfig(array $config, string $serviceName): array
	{
	    $result = [];
	    if ($config['autoload']['psr-4'] ?? null) {
	        $result['autoload.psr-4'] = $config['autoload']['psr-4'];
        }

        $configService = $config['service'] ?? [];
        if (is_string($configService)) {
            $configService = ['name' => $configService];
        }

	    $result = array_merge($result, $configService);

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

	public static function preparePluginConfig(array $config, string $pluginName, ?string $prototype): array
	{
        $commonConfig = lx::$app->getDefaultPluginConfig();
		self::prepareConfig($commonConfig, $config);

		foreach ($commonConfig as $key => $value) {
			if ($key == 'aliases') {
				$config['aliases'] += $value;
			} elseif (!array_key_exists($key, $config)) {
				$config[$key] = $value;
			}
		}

        ConfigHelper::pluginInject($pluginName, $prototype, $config);
        return $config;
	}


    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * PRIVATE
     * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

    private static function injectServiceConfig(string $name, array $config): array
    {
        $allInjections = lx::$app->getConfig('configInjection');

        if (!is_array($allInjections) || !array_key_exists($name, $allInjections)) {
            return $config;
        }

        $injections = $allInjections[$name];
        return ArrayHelper::mergeRecursiveDistinct($config, $injections, true, true);
    }

	private static function pluginInject(string $name, ?string $prototype, array &$config): void
	{
        $injections = lx::$app->getConfig('configInjection') ?? [];
        if (empty($injections)) {
            return;
        }

        if (array_key_exists($prototype, $injections)) {
            $config = ArrayHelper::mergeRecursiveDistinct(
                $config,
                $injections[$prototype],
                true,
                true
            );
        }

        if (array_key_exists($name, $injections)) {
            $config = ArrayHelper::mergeRecursiveDistinct(
                $config,
                $injections[$name],
                true,
                true
            );
        }
	}

	private static function prepareConfig(array &$commonConfig, array &$config): void
	{
		$useAliases = $commonConfig['useCommonAliases'] ??false;
		unset($commonConfig['useCommonAliases']);
		if ($useAliases) {
			if (!isset($config['aliases'])) {
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
