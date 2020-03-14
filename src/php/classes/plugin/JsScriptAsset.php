<?php

namespace lx;

/**
 * Class JsScriptAsset
 * @package lx
 */
class JsScriptAsset
{
	const LOCATION_HEAD = 'head';
	const LOCATION_BODY_BEGIN = 'body-begin';
	const LOCATION_BODY_END = 'body-end';

	/** @var Plugin */
	private $plugin;

	/** @var string */
	private $path;

	/** @var string */
	private $location;

	/** @var bool */
	private $parallel;

	/** @var string */
	private $onLoad;

	/** @var string */
	private $onError;

	/**
	 * JsScriptAsset constructor.
	 * @param array|string $config
	 */
	public function __construct($plugin, $config)
	{
		$this->plugin = $plugin;

		if (is_string($config)) {
			$config = [
				'script' => $config
			];
		}

		$this->path = $this->plugin->conductor->getAssetPath($config['script'] ?? $config['path']);
		$this->location = $config['location'] ?? self::LOCATION_HEAD;
		$this->parallel = $config['parallel'] ?? false;
		$this->onLoad = $config['onLoad'] ?? '';
		$this->onError = $config['onError'] ?? '';
	}

	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$result = [
			'path' => $this->path
		];

		if ($this->location != self::LOCATION_HEAD) {
			$result['location'] = $this->location;
		}

		if ($this->parallel) {
			$result['parallel'] = true;
		}

		if ($this->onLoad != '') {
			$result['onLoad'] = $this->onLoad;
		}

		if ($this->onError != '') {
			$result['onError'] = $this->onError;
		}

		return $result;
	}
}
