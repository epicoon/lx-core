<?php

namespace lx;

class HtmlBody
{
    const LXID_BODY = '_1';
    const LXID_TOSTS = '_2';
    const LXID_ALERTS = '_3';

	private string $js;
	private array $beginScripts = [];
	private array $endScripts = [];

	public function __construct(array $pageData, string $js)
	{
		$this->js = $js;
		$this->initScripts($pageData);
	}

	public function render(): string
	{
        $script = 'lx.getBodyElement=()=>{return document.querySelector("[lxid^=\'' . self::LXID_BODY . '\']");};'
            . 'lx.getTostsElement=()=>{return document.querySelector("[lxid^=\'' . self::LXID_TOSTS . '\']");};'
            . 'lx.getAlertsElement=()=>{return document.querySelector("[lxid^=\'' . self::LXID_ALERTS . '\']");};';

        $cssClass = 'lxbody-' . \lx::$app->cssManager->getDefaultCssPreset();
		return
            '<script>' . $script . '</script>'.
			$this->renderBeginScripts() .
			'<div lxid=' . self::LXID_ALERTS . ' class="' . $cssClass . '"></div>' .
            '<div lxid=' . self::LXID_TOSTS . ' class="' . $cssClass . '"></div>' .
            '<div lxid=' . self::LXID_BODY . ' class="' . $cssClass . '"></div>' .
			'<script id=__js>document.body.removeChild(document.getElementById("__js"));' .
			$this->js . '</script>' .
			$this->renderEndScripts();
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function renderBeginScripts(): string
	{
		$result = '';
		foreach ($this->beginScripts as $script) {
			$result .= HtmlHelper::rendetScriptTag($script);
		}
		return $result;
	}

	private function renderEndScripts(): string
	{
		$result = '';
		foreach ($this->endScripts as $script) {
			$result .= HtmlHelper::rendetScriptTag($script);
		}
		return $result;
	}

	private function initScripts(array $data): void
	{
		$scripts = $data['scripts'] ?? null;
		if (!$scripts) {
			return;
		}

		foreach ($scripts as $pluginKey => $script) {
			$location = $script['location'] ?? JsScriptAsset::LOCATION_HEAD;
			if ($location == JsScriptAsset::LOCATION_BODY_TOP) {
				$this->beginScripts[$pluginKey] = $script;
			} elseif ($location == JsScriptAsset::LOCATION_BODY_BOTTOM) {
				$this->endScripts[$pluginKey] = $script;
			}
		}
	}
}
