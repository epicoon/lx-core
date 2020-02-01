<?php

namespace lx;

class HtmlHead {
	use ApplicationToolTrait;

	private $title = 'lx';
	private $icon = null;
	private $css = null;
	private $scripts = null;

	public function __construct($config) {
		$config = DataObject::create($config);
		if ($config->title) $this->title = $config->title;
		if ($config->icon) $this->icon = $config->icon;
		if ($config->css) $this->css = $config->css;
		if ($config->scripts) $this->scripts = $config->scripts;
	}

	public function render() {
		$result = "<title>{$this->title}</title>";
		$result .= $this->getLxCss();
		$result .= $this->getIcon();
		$result .= $this->getCss();
		$result .= $this->getScripts();
		return $result;
	}

	private function getLxCss() {
		//TODO конфигурирование для отключения проверки
		$this->recompileLxCss();

		$result = '<link href="';
		$result .= ($this->getRelPath() . '/css/lx.css');
		$result .= '" type="text/css" rel="stylesheet">';
		return $result;
	}

	private function getIcon() {
		$result = '<link rel="shortcut icon" href="';
		$result .= $this->icon
			? $this->icon
			: $this->getRelPath() . '/img/icon.png';
		$result .= '" type="image/png">';
		return $result;
	}

	private function getCss() {
		$result = '';
		if (!$this->css) {
			return $result;
		}

		foreach ($this->css as $key => $css) {
			foreach ($css as $path) {
				$result .= "<link href=\"$path\" name=\"$key\" type=\"text/css\" rel=\"stylesheet\">";
			}
		}

		return $result;
	}

	private function getScripts() {
		$result = '';
		if (!$this->scripts) {
			return $result;
		}

		foreach ($this->scripts as $key => $scripts) {
			foreach ($scripts as $path) {
				$result .= "<script src=\"$path\" name=\"$key\"></script>";
			}
		}
		
		return $result;
	}

	private function getRelPath() {
		return explode($this->app->sitePath, \lx::$conductor->getSystemPath('core'))[1];
	}

	private function recompileLxCss() {
		$corePath = \lx::$conductor->getSystemPath('core');
		$cssFile = new File($corePath . '/css/lx.css');
		$cssJsFile = new File($corePath . '/css/lx.css.js');
		if ($cssJsFile->exists() && $cssJsFile->isNewer($cssFile)) {
			$exec = new NodeJsExecutor();
			$res = $exec->runFile($cssJsFile, ['@core/js/classes/css/CssContext']);
			$cssFile->put($res);
		}		
	}
}
