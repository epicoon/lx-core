<?php

namespace lx;

class JsScriptAsset
{
	const LOCATION_HEAD = 'head';
	const LOCATION_BODY_TOP = 'body-top';
	const LOCATION_BODY_BOTTOM = 'body-bottom';

	private Plugin $plugin;
	private string $path;
	private string $location;
	private string $onLoad;
	private string $onError;

	/**
	 * @param array|string $config
	 */
	public function __construct(Plugin $plugin, $config)
	{
		$this->plugin = $plugin;

		if (is_string($config)) {
			$config = [
				'script' => $config
			];
		}

		$this->path = $this->plugin->conductor->getAssetPath($config['script'] ?? $config['path']);
		$this->location = $config['location'] ?? self::LOCATION_HEAD;
		$this->onLoad = $config['onLoad'] ?? '';
		$this->onError = $config['onError'] ?? '';
	}

	public function getPath(): string
	{
		return $this->path;
	}

    public function setPath(string $path): void
    {
        $this->path = $path;
    }
    
	public function toArray(): array
	{
		$result = [
			'path' => $this->path
		];

		if ($this->location != self::LOCATION_HEAD) {
			$result['location'] = $this->location;
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
