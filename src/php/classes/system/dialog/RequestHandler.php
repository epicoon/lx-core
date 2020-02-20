<?php

namespace lx;

/**
 * Class RequestHandler
 * @package lx
 */
class RequestHandler extends Object
{
	use ApplicationToolTrait;

	/** @var int */
	private $code;
	/** @var SourceContext */
	private $sourceContext;

	/**
	 * - определяется запрашиваемый ресурс
	 * - аутентифицируется запрашивающий пользователь
	 * - проверяются права пользователя на ресурс
	 * - устанавливается основной код ответа (200, 403, 404)
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
	 * Отправка ресурса в зависимости от основного кода ответа
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

	private function trySend()
	{
		if ( ! $this->sourceContext) {
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
			if ($sourceContext) {
				$this->renderPlugin($sourceContext->invoke());
				return;
			}
		}

		$this->beforeFailedSending();

		if ($this->app->dialog->isPageLoad()) {
			$this->renderStandartResponse($this->code);
		} else {
			$this->app->dialog->send([
				'success' => false,
				'error' => $this->code,
			]);
		}

		$this->afterFailedSending();
	}

	/**
	 * @param $plugin Plugin
	 */
	private function renderPlugin($pluginData)
	{
		// Информация о самом плагине
		$pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

		// Собираем код модулей
		$modules = '';
		if (!empty($pluginData['modules'])) {
			$moduleProvider = new JsModuleProvider();
			$modules = $moduleProvider->getModulesCode($pluginData['modules']);
			$modules = addcslashes($modules, '\\');
		}

		// Глобальный код, глобальные настройки
		list($jsCore, $jsBootstrap, $jsMain) = $this->app->getCommonJs();
		$settings = ArrayHelper::arrayToJsCode( $this->app->getSettings() );
		$js = $jsCore . "lx.start($settings, `$modules`, `$jsBootstrap`, `$pluginInfo`, `$jsMain`);";

		$head = new HtmlHead($pluginData['page']);
		$this->renderStandartResponse(ResponseCodeEnum::OK, ['head' => $head, 'js' => $js]);
	}

	private function renderStandartResponse($code, $params = [])
	{
		$path = \lx::$conductor->getSystemPath('stdResponses') . '/' . $code . '.php';
		if (!file_exists($path)) {
			$path = \lx::$conductor->getSystemPath('stdResponses') . '/404.php';
		}

		extract($params);
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
