<?php

namespace lx;

/**
 * Class HtmlBody
 * @package lx
 */
class HtmlBody
{
	/** @var string */
	private $js;

	/** @var array */
	private $beginScripts = [];

	/** @var array */
	private $endScripts = [];

	/**
	 * HtmlBody constructor.
	 * @param array $pageData
	 * @param string $js
	 */
	public function __construct($pageData, $js)
	{
		$this->js = $js;
		$this->initScripts($pageData);
	}

	/**
	 * @return string
	 */
	public function render()
	{
		return
			$this->renderBeginScripts() .
			'<div lxid=' . WidgetHelper::LXID_ALERTS . ' class="lxbody"></div>' .
			'<div lxid=' . WidgetHelper::LXID_TOSTS . ' class="lxbody"></div>' .
			'<div lxid=' . WidgetHelper::LXID_BODY . ' class="lxbody"></div>' .
			'<script id=__js>document.body.removeChild(document.getElementById("__js"));' .
			$this->js . '</script>' .
			$this->renderEndScripts();
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @return string
	 */
	private function renderBeginScripts()
	{
		$result = '';
		foreach ($this->beginScripts as $script) {
			$result .= HtmlHelper::rendetScriptTag($script);
		}
		return $result;
	}

	/**
	 * @return string
	 */
	private function renderEndScripts()
	{
		$result = '';
		foreach ($this->endScripts as $script) {
			$result .= HtmlHelper::rendetScriptTag($script);
		}
		return $result;
	}

	/**
	 * @param array $data
	 */
	private function initScripts($data)
	{
		$scripts = $data['scripts'] ?? null;
		if (!$scripts) {
			return;
		}

		foreach ($scripts as $pluginKey => $script) {
			$location = $script['location'] ?? JsScriptAsset::LOCATION_HEAD;
			if ($location == JsScriptAsset::LOCATION_BODY_BEGIN) {
				$this->beginScripts[$pluginKey] = $script;
			} elseif ($location == JsScriptAsset::LOCATION_BODY_END) {
				$this->endScripts[$pluginKey] = $script;
			}
		}
	}
}
