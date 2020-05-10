<?php

require_once(__DIR__ . '/PlatformConductor.php');

/**
 * Static environment for application
 *
 * Class lx
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

	const POSTUNPACK_TYPE_IMMEDIATLY = 1;
	const POSTUNPACK_TYPE_FIRST_DISPLAY = 2;
	const POSTUNPACK_TYPE_ALL_DISPLAY = 3;

	/** @var \lx\Autoloader */
	public static $autoloader;

	/** @var lx\PlatformConductor */
	public static $conductor;

	/** @var \lx\HttpApplication|\lx\ConsoleApplication|\lx\ProcessApplication */
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

		require_once(__DIR__ . '/classes/system/autoload/Autoloader.php');
		self::$autoloader = lx\Autoloader::getInstance();
		self::$autoloader->init(self::$conductor->sitePath, dirname(__DIR__));

		self::$dump = '';
	}

    /**
     * @param string $command
     * @param bool $async
     * @return string|null
     */
	public static function exec($command, $async = false)
    {
        if (is_array($command)) {
            $str = $command['executor'] . ' ' . $command['script'];
            if (array_key_exists('args', $command)) {
                $args = [];
                foreach ($command['args'] as $arg) {
                    $args[] = '"' . addcslashes($arg, '"\\') . '"';
                }
                $str .= ' ' . implode(' ', $args);
            }
            $command = $str;
        }
        
        if ($async) {
            $command .= ' > /dev/null 2>/dev/null &';
        }

        return shell_exec($command);
    }

	/**
	 * Advanced way to dump some information in browser
	 *
	 * @param mixed $data
	 */
	public static function echo($data)
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
	public static function dump($data)
	{
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		ob_start();
		var_dump($data);
		$out = ob_get_clean();
		self::echo($out);
	}

	/**
	 * @param array|string $data
	 */
	public static function devLog($data)
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
	 *
	 * @return string
	 */
	public static function getDump()
	{
		return self::$dump;
	}
}
