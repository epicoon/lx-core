<?php

namespace lx;

/**
 * Interface ApplicationLifeCycleManagerInterface
 * @package lx
 */
interface ApplicationLifeCycleManagerInterface
{
	/**
	 * Method called as the first step in [[\lx::$app->run()]]
	 */
	public function beforeRun();

	/**
	 * Method called as last step in [[\lx::$app->run()]]
	 */
	public function afterRun();

	/**
	 * Method called as the first step in [[\lx\PluginConductor::getCssAssets()]]
	 *
	 * @param Plugin $plugin
	 */
	public function beforeGetPluginCssAssets($plugin);

	/**
	 * Method called as last step in:
	 * - [[\lx\Plugin::getScripts()]]
	 * - [[\lx\Plugin::getCss()]]
	 * - [[\lx\Plugin::getImagePathes()]]
	 *
	 * @param array $originalPathes
	 * @param array $linkPathes
	 */
	public function beforeReturnAutoLinkPathes($originalPathes, $linkPathes);
}
