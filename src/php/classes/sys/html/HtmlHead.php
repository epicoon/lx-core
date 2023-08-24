<?php

namespace lx;

use lx;

class HtmlHead
{
	private string $title = 'lx';
	private ?string $icon = null;
	private array $pluginCss = [];
    private array $moduleCss = [];
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
		if ($config->pluginCss) {
			$this->pluginCss = $config->pluginCss;
		}
        if ($config->moduleCss) {
            $this->moduleCss = $config->moduleCss;
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

    private function getIcon(): string
    {
        return '<link rel="shortcut icon" href="'
            . ($this->icon ? $this->icon : ($this->getWebLxPath() . '/icon.png'))
            . '" type="image/png">';
    }

	private function getLxCss(): string
	{
        $mainCss = lx::$app->cssManager->isBuildType(CssManager::BUILD_TYPE_SEGREGATED)
            ? 'main-' . lx::$app->cssManager->getDefaultCssPresetName() . '.css'
            : 'main.css';
        $commonCss = '<link href="' . ($this->getWebLxPath() . '/' . $mainCss)
			. '" name="base_css" type="text/css" rel="stylesheet">';

        $modules = lx::$app->jsModules->getCoreModules();
        $cssList = lx::$app->jsModules->getModulesCss($modules);
        foreach ($cssList as $css) {
            $commonCss .= '<link href="' . $css . '" name="base_css" type="text/css" rel="stylesheet">';
        }

        return $commonCss;
	}

    private function getCss(): string
    {
        $result = '';
        foreach ($this->moduleCss as $css) {
            $result .= "<link href=\"$css\" name=\"module_asset\" type=\"text/css\" rel=\"stylesheet\">";
        }
        foreach ($this->pluginCss as $css) {
            $result .= "<link href=\"$css\" name=\"plugin_asset\" type=\"text/css\" rel=\"stylesheet\">";
        }
        return $result;
    }

	private function getLxJs(): string
	{
		$path = $this->getWebLxPath() . '/core.js';
		return '<script src="' . $path . '"></script>';
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

	private function getWebLxPath(): string
	{
        /** @var RouterInterface|null $router */
        $router = lx::$app->router;
        $prefix = $router ? $router->getAssetPrefix() : '';
		return $prefix . '/' . lx::$app->conductor->getRelativePath(lx::$conductor->webLx);
	}
}
