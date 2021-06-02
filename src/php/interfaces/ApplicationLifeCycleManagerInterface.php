<?php

namespace lx;

interface ApplicationLifeCycleManagerInterface
{
	/**
	 * Method called as the first step in [[lx::$app->run()]]
	 */
	public function beforeRun(): void;

	/**
	 * Method called as the last step in [[lx::$app->run()]]
	 */
	public function afterRun(): void;

	/**
	 * Method called as the first step in [[lx\PluginConductor::getCssAssets()]]
	 */
	public function beforeGetPluginCssAssets(Plugin $plugin): void;

	/**
	 * Method called as the last step in:
	 * - [[lx\Plugin::getScripts()]]
	 * - [[lx\Plugin::getCss()]]
	 * - [[lx\Plugin::getImagePathes()]]
	 */
	public function beforeReturnAutoLinkPathes(array $originalPathes, array $linkPathes): void;
}
