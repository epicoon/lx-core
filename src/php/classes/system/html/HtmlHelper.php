<?php

namespace lx;

class HtmlHelper
{
	public static function rendetScriptTag(array $data): string
	{
		$path = $data['path'];
		$code = [
			"<script src=\"$path\" name=\"plugin_asset\"",
		];

		if (array_key_exists('onLoad', $data)) {
			$onLoad = preg_replace('/(?:\'|\\' . '\')/', '"', $data['onLoad']);
			$code[] = "onLoad='{$onLoad}'";
		}

		if (array_key_exists('onError', $data)) {
			$onError = preg_replace('/(?:\'|\\' . '\')/', '"', $data['onError']);
			$code[] = "onError='{$onError}'";
		}

		return implode(' ', $code) . '></script>';
	}
}
