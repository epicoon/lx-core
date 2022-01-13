<?php

namespace lx;

class HtmlHead
{
	private string $title = 'lx';
	private ?string $icon = null;
	private ?array $css = null;
	private ?array $scripts = null;

	public function __construct(array $config)
	{
		$config = DataObject::create($config);
		if ($config->title) {
			$this->title = $config->title;
		}
		if ($config->icon) {
			$this->icon = $config->icon;
		}
		if ($config->css) {
			$this->css = $config->css;
		}
		if ($config->scripts) {
			$this->scripts = $config->scripts;
		}
	}

	public function render(): string
	{
		return "<title>{$this->title}</title>"
			. $this->getIcon()
			. $this->getLxCss()
			. $this->getCss()
			. $this->getLxJs()
			. $this->getScripts();
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function getLxCss(): string
	{
		return '<link href="'
			. ($this->getCssPath() . '/main.css')
			. '" name="base_css" type="text/css" rel="stylesheet">';
	}

	private function getLxJs(): string
	{
		$path = $this->getJsPath() . '/core.js';
		return '<script src="' . $path . '"></script>';
	}

	private function getIcon(): string
	{
		return '<link rel="shortcut icon" href="'
			. ($this->icon ? $this->icon : ($this->getCssPath() . '/img/icon.png'))
			. '" type="image/png">';
	}

	private function getCss(): string
	{
		$result = '';
		if (!$this->css) {
			return $result;
		}

		foreach ($this->css as $css) {
			$result .= "<link href=\"$css\" name=\"plugin_asset\" type=\"text/css\" rel=\"stylesheet\">";
		}

		return $result;
	}

	private function getScripts(): string
	{
		$result = '';
		if (!$this->scripts) {
			return $result;
		}

		foreach ($this->scripts as $script) {
			$path = $script['path'];
			$location = $script['location'] ?? JsScriptAsset::LOCATION_HEAD;
			if ($location == JsScriptAsset::LOCATION_HEAD) {
				$result .= HtmlHelper::rendetScriptTag($script);
			}
		}

		return $result;
	}

    private function getCssPath(): string
    {
        return '/' . \lx::$app->conductor->getRelativePath(\lx::$conductor->webCss);
    }

	private function getJsPath(): string
	{
		return '/' . \lx::$app->conductor->getRelativePath(\lx::$conductor->webJs);
	}
}
