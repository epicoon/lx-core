<?php

namespace lx;

/**
 * Class RequestHandler
 * @package lx
 */
class RequestHandler extends BaseObject
{
	use ApplicationToolTrait;

	/** @var int */
	private $code;

	/** @var SourceContext */
	private $sourceContext;

	/**
	 * Launch of the response preparing
	 */
	public function run()
	{
		if (!$this->getSource()) {
			$this->code = ResponseCodeEnum::NOT_FOUND;
			return;
		}

		$this->code = ResponseCodeEnum::OK;
	}

	/**
	 * Send the response
	 */
	public function send()
	{
		if ($this->code == ResponseCodeEnum::OK) {
			$this->trySend();
		} else {
			$this->sendNotOk();
		}
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @return bool
	 */
	private function getSource()
	{
		if (SpecialAjaxRouter::checkDialog()) {
			$ajaxRouter = new SpecialAjaxRouter();
			$sourceContext = $ajaxRouter->route();
			if ($sourceContext !== false) {
				$this->sourceContext = $sourceContext;
			}
		} else {
			$router = $this->app->router;
			if ($router !== null) {
				$sourceContext = $router->route();
				if ($sourceContext !== false) {
					$this->sourceContext = $sourceContext;
				}
			}
		}

		return $this->sourceContext !== null;
	}

	/**
	 * Try to send OK-response or will be sent error response
	 */
	private function trySend()
	{
		if (!$this->sourceContext) {
			$this->sendNotOk(ResponseCodeEnum::FORBIDDEN);
			return;
		}

		$result = $this->sourceContext->invoke();
		if ($result instanceof SourceError) {
			$this->code = $result->getCode();
			$this->sendNotOk();
			return;
		}

		if ($this->sourceContext->isPlugin() && $this->app->dialog->isPageLoad()) {
			$this->renderPlugin($result);
			$result = null;
		}

		$this->beforeSuccessfulSending();
		$this->app->dialog->send($result);
		$this->afterSuccessfulSending();
	}

	/**
	 * @param int $code
	 */
	private function sendNotOk($code = null)
	{
		if ($code) {
			$this->code = $code;
		}

		if ($this->code == ResponseCodeEnum::FORBIDDEN
			&& $this->app->dialog->isPageLoad()
			&& $this->app->user->isGuest()
		) {
			$sourceContext = $this->app->authenticationGate->responseToAuthenticate() ?? null;
			if ($sourceContext && $sourceContext->isPlugin()) {
				$this->renderPlugin($sourceContext->invoke());
				return;
			}
		}

		$this->beforeFailedSending();

		if ($this->app->dialog->isPageLoad()) {
			$this->renderStandartResponse($this->code);
			$this->app->dialog->send();
		} elseif ($this->app->dialog->isAssetLoad()) {
			$url = $this->app->dialog->getUrl();
			$assetName = 'unknown';
			switch (true) {
				case preg_match('/\.js$/', $url):
					$assetName = 'javascript file';
			}
			$msg = "Asset ($assetName) \"$url\" not found";
			$this->app->dialog->send('console.error(\'' . $msg . '\');');
		} else {
			$this->app->dialog->send([
				'success' => false,
				'error' => $this->code,
			]);
		}

		$this->afterFailedSending();
	}

	/**
	 * @param array $pluginData
	 */
	private function renderPlugin($pluginData)
	{
		$pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

		$modules = '';
		if (!empty($pluginData['modules'])) {
			$moduleProvider = new JsModuleProvider();
			$modules = $moduleProvider->getModulesCode($pluginData['modules']);
			$modules = addcslashes($modules, '\\');
		}

		list($jsBootstrap, $jsMain) = $this->app->getCommonJs();
		$settings = ArrayHelper::arrayToJsCode($this->app->getSettings());
		$js = "lx.start($settings, `$modules`, `$jsBootstrap`, `$pluginInfo`, `$jsMain`);";

		$this->renderStandartResponse(ResponseCodeEnum::OK, [
			'head' => new HtmlHead($pluginData['page']),
			'body' => new HtmlBody($pluginData['page'], $js),
		]);
	}

	/**
	 * @param int $code
	 * @param array $params
	 */
	private function renderStandartResponse($code, $params = [])
	{
		$path = \lx::$conductor->stdResponses . '/' . $code . '.php';
		if (!file_exists($path)) {
			$path = \lx::$conductor->stdResponses . '/404.php';
		}

		extract($params);
		ob_start();
		require_once($path);
	}

	private function beforeSuccessfulSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('beforeSending');
			$this->sourceContext->invoke('beforeSuccessfulSending');
		}
	}

	private function beforeFailedSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('beforeSending');
			$this->sourceContext->invoke('beforeFailedSending');
		}
	}

	private function afterSuccessfulSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('afterSuccessfulSending');
			$this->sourceContext->invoke('afterSending');
		}
	}

	private function afterFailedSending()
	{
		if ($this->sourceContext) {
			$this->sourceContext->invoke('afterFailedSending');
			$this->sourceContext->invoke('afterSending');
		}
	}
}
