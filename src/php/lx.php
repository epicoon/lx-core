<?php

require_once(__DIR__ . '/PlatformConductor.php');

class lx {
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

	/** @var lx\PlatformConductor */
	public static $conductor;
	public static $app;

	public static $dump;

	public static function init() {
		self::$conductor = new lx\PlatformConductor();
		require_once(__DIR__ . '/classes/system/autoload/Autoloader.php');
		$autoloader = lx\Autoloader::getInstance();
		$autoloader->init(self::$conductor->sitePath);

		self::$dump = '';
	}

	public static function echo($data) {
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		if (!is_string($data)) {
			$data = json_encode($data);
		}

		self::$dump .= $data;
	}

	public static function dump($data) {
		if (self::$app->isMode(self::MODE_PROD)) {
			return;
		}

		ob_start();
		var_dump($data);
		$out = ob_get_clean();
		self::echo($out);
	}
}
