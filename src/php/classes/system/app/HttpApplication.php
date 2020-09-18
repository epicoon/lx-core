<?php

namespace lx;

/**
 * Class HttpApplication
 * @package lx
 *
 * @property-read Router $router
 * @property-read Dialog $dialog
 * @property-read User $user
 */
class HttpApplication extends BaseApplication
{
	/**
	 * HttpApplication constructor.
     * @param array $config
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
        $this->settings = [
			'unpackType' => \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
		];
	}

    /**
     * @return array
     */
	protected static function getDefaultComponents()
    {
        return array_merge(parent::getDefaultComponents(), [
            'router' => Router::class,
            'dialog' => Dialog::class,
            'user' => User::class,
        ]);
    }

	public function run()
	{
		if ($this->lifeCycle) {
			$this->lifeCycle->beforeRun();
		}

		$this->authenticateUser();

		$requestHandler = RequestHandler::create();
		$requestHandler->run();
		$requestHandler->send();

		if ($this->lifeCycle) {
			$this->lifeCycle->afterRun();
		}
	}

	/**
	 * @return array
	 */
	public function getCommonJs()
	{
		$compiler = new JsCompiler();

		$jsBootstrap = $this->compileJsBootstrap($compiler);
		$jsMain = $this->compileJsMain($compiler);
		$jsBootstrap = addcslashes($jsBootstrap, '\\');
		$jsMain = addcslashes($jsMain, '\\');

		return [$jsBootstrap, $jsMain];
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * Method defines current user
	 */
	private function authenticateUser()
	{
		if ($this->user && $this->authenticationGate) {
			$this->authenticationGate->authenticateUser();
		}
	}

	/**
	 * Global JS-code executed before plugin rise
	 *
	 * @param JsCompiler $compiler
	 * @return string
	 */
	private function compileJsBootstrap($compiler)
	{
		$path = $this->getConfig('jsBootstrap');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}

	/**
	 * Global JS-code executed after plugin rise
	 *
	 * @param JsCompiler $compiler
	 * @return string
	 */
	private function compileJsMain($compiler)
	{
		$path = $this->getConfig('jsMain');
		if (!$path) {
			return '';
		}

		$path = $this->conductor->getFullPath($path);
		if (!file_exists($path)) {
			return '';
		}

		$code = file_get_contents($path);
		$code = $compiler->compileCode($code, $path);
		if (!$code) {
			return '';
		}

		return $code;
	}
}
