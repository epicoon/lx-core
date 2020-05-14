<?php

namespace lx;

/**
 * Class HtmlHead
 * @package lx
 */
class HtmlHead extends BaseObject
{
	use ApplicationToolTrait;

	/** @var string */
	private $title = 'lx';

	/** @var string */
	private $icon = null;

	/** @var array */
	private $css = null;

	/** @var array */
	private $scripts = null;

	/**
	 * HtmlHead constructor.
	 * @param array $config
	 */
	public function __construct($config)
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

	/**
	 * @return string
	 */
	public function render()
	{
		return "<title>{$this->title}</title>"
			. $this->getIcon()
			. $this->getLxCss()
			. $this->getCss()
			. $this->getLxJs()
			. $this->js . '</script>'
			. $this->getScripts();
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @return string
	 */
	private function getLxCss()
	{
		return '<link href="'
			. ($this->getCssPath() . '/main.css')
			. '" type="text/css" rel="stylesheet">';
	}

	/**
	 * @return string
	 */
	private function getLxJs()
	{
		$path = $this->getJsPath() . '/core.js';
		return '<script src="' . $path . '"></script>';
	}

	/**
	 * @return string
	 */
	private function getIcon()
	{
		return '<link rel="shortcut icon" href="'
			. ($this->icon ? $this->icon : ($this->getCssPath() . '/img/icon.png'))
			. '" type="image/png">';
	}

	/**
	 * @return string
	 */
	private function getCss()
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

	/**
	 * @return string
	 */
	private function getScripts()
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

    /**
     * @return string
     */
    private function getCssPath()
    {
        return '/' . $this->app->conductor->getRelativePath(\lx::$conductor->webCss);
    }

	/**
	 * @return string
	 */
	private function getJsPath()
	{
		return '/' . $this->app->conductor->getRelativePath(\lx::$conductor->webJs);
	}
}
