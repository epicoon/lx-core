<?php

namespace lx;

/**
 * @property-read HttpAssetsManager $assets
 * @property-read Router $router
 * @property-read Dialog $dialog
 */
class HttpApplication extends BaseApplication
{
	public function __construct(array $config = [])
	{
		parent::__construct($config);
        $this->settings = [
			'unpackType' => \lx::POSTUNPACK_TYPE_FIRST_DISPLAY,
		];
	}

    public function getDefaultFusionComponents(): array
    {
        return array_merge(parent::getDefaultFusionComponents(), [
            'assets' => HttpAssetsManager::class,
            'router' => Router::class,
            'dialog' => Dialog::class,
            'user' => UserInterface::class,
        ]);
    }

	public function run(): void
	{
	    $this->events->trigger(self::EVENT_BEFORE_RUN);
	    
		$this->authenticateUser();
		$requestHandler = RequestHandler::create();
		$requestHandler->run();
		$requestHandler->send();

		$this->events->trigger(self::EVENT_AFTER_RUN);
	}

	public function getCommonJs(): array
	{
		$compiler = new JsCompiler();

		$jsBootstrap = $this->compileJsBootstrap($compiler);
		$jsMain = $this->compileJsMain($compiler);
		$jsBootstrap = addcslashes($jsBootstrap, '\\');
		$jsMain = addcslashes($jsMain, '\\');

		return [$jsBootstrap, $jsMain];
	}


	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * PRIVATE
	 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	private function authenticateUser(): void
	{
		if ($this->user && $this->userManager && $this->authenticationGate) {
			$this->authenticationGate->authenticateUser();
		}
	}

	private function compileJsBootstrap(JsCompiler $compiler): string
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

	private function compileJsMain(JsCompiler $compiler): string
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
