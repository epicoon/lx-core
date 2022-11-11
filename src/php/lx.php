<?php

require_once(__DIR__ . '/PlatformConductor.php');

/**
 * Static environment for application
 */
class lx
{
	const MODE_PROD = 'prod';
	const MODE_DEV = 'dev';
	const MODE_TEST = 'test';

	const LEFT = 1;
	const CENTER = 2;
	const WIDTH = 2;
	const RIGHT = 3;
	const JUSTIFY = 4;
	const TOP = 5;
	const MIDDLE = 6;
	const HEIGHT = 6;
	const BOTTOM = 7;
	const VERTICAL = 1;
	const HORIZONTAL = 2;

	/** @var \lx\Autoloader */
	public static $autoloader;

	/** @var lx\PlatformConductor */
	public static $conductor;

	/** @var \lx\AbstractApplication */
	public static $app;

	/** @var string */
	private static $dump;
	
	/** @var \lx\DevLogger */
	private static $devLogger;

	/**
	 * Initialisation of platform conductor and autoloader
	 */
	public static function init()
	{
		self::$conductor = new lx\PlatformConductor();

		require_once(__DIR__ . '/classes/sys/autoload/Autoloader.php');
		self::$autoloader = lx\Autoloader::getInstance();
		self::$autoloader->init(self::$conductor->sitePath, dirname(__DIR__));

		self::$dump = '';
	}

	/**
	 * Advanced way to dump some information in browser
	 *
	 * @param mixed $data
	 */
	public static function echo($data): void
	{
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		if (!is_string($data)) {
			$data = json_encode($data);
		}

		self::$dump .= $data;
	}

	/**
	 * Advanced way to dump some information in browser
	 *
	 * @param mixed $data
	 */
	public static function dump($data): void
	{
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		$out = self::getDumpString($data);
		self::echo($out);
	}

    /**
     * @param mixed $data
     * @return false|string
     */
	public static function getDumpString($data)
    {
        ob_start();
        var_dump($data);
        return ob_get_clean();
    }

	/**
	 * @param array|string $data
	 */
	public static function devLog($data): void
	{
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		if ( ! self::$devLogger) {
			self::$devLogger = new \lx\DevLogger();
			self::$devLogger->truncate();
		}

		self::$devLogger->log($data, self::$app->getMode() ?? 'common');
	}

	/**
	 * Method for recieving dumped data while response preparing
	 * You don't need to use this method
	 */
	public static function getDump(): string
	{
		return self::$dump;
	}
}
