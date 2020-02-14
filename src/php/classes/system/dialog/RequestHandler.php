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
	private $source;

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

		if (!$this->checkAccess()) {
			$this->code = ResponseCodeEnum::FORBIDDEN;
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
			$source = $ajaxRouter->route();
			if ($source !== false) {
				$this->source = $source;
			}
		} else {
			$router = $this->app->router;
			if ($router !== null) {
				$source = $router->route();
				if ($source !== false) {
					$this->source = $source;
				}
			}
		}

		return $this->source !== null;
	}

	/**
	 * @return bool
	 */
	private function checkAccess()
	{
		// Если нет компонента "пользователь"
		if (!$this->app->user) {
			return true;
		}

		// Если есть компонент аутентификации, получим пользователя
		if ($this->app->authenticationGate) {
			$this->app->authenticationGate->authenticateUser();
		}

		// Если есть компонент авторизации, проверим права пользователя
		if ($this->app->authorizationGate) {
			$this->source = $this->app->authorizationGate->checkAccess(
				$this->app->user,
				$this->source
			);
		}

		// Если при авторизации было наложено ограничение
		if ($this->source->hasRestriction()) {
			if ($this->source->getRestriction() == SourceContext::RESTRICTION_INSUFFICIENT_RIGHTS
				&& $this->app->user->isGuest()
				&& $this->app->dialog->isPageLoad()
			) {
				$this->source = $this->app
					->authenticationGate
					->responseToAuthenticate($this->source);
			} else {
				return false;
			}
		}

		return true;
	}

	private function trySend()
	{
		if ($this->source === false) {
			$this->sendNotOk(ResponseCodeEnum::FORBIDDEN);
			return;
		}

		$result = false;
		if ($this->source->isPlugin()) {
			$plugin = $this->source->getPlugin();
			if ($this->app->dialog->isPageLoad()) {
				$this->renderPlugin($plugin);
				$result = null;
			} else {
				$builder = new PluginBuildContext($plugin);
				$result = $builder->build();
			}
		} else {
			$result = $this->source->invoke();
			if ($result instanceof SourceError) {
				$this->code = $result->getCode();
				$result = false;
			}
		}

		if ($result === false) {
			$this->sendNotOk();
			return;
		}

		$this->beforeSuccessfulSending();
		$this->app->dialog->send($result);
		$this->afterSuccessfulSending();
	}

	private function sendNotOk($code = null)
	{
		$this->beforeFailedSending();

		if ($code) {
			$this->code = $code;
		}

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
	private function renderPlugin($plugin)
	{
		if (!$plugin) {
			$this->renderStandartResponse(ResponseCodeEnum::NOT_FOUND);
			return;
		}

		// Помечаем плагин как собирающийся при загрузке страницы
		$plugin->setMain(true);

		$context = new PluginBuildContext($plugin);
		list($jsCore, $jsBootstrap, $jsMain) = $this->app->getCommonJs();

		$pluginData = $context->build();
		if (!empty($pluginData['modules'])) {
			$moduleProvider = new JsModuleProvider();
			$pluginData['pluginInfo'] .= '<modules>'
				. $moduleProvider->getModulesCode($pluginData['modules'])
				. '</modules>';
		}
		$pluginInfo = addcslashes($pluginData['pluginInfo'], '\\');

		// Глобальные настройки
		$settings = ArrayHelper::arrayToJsCode( $this->app->getSettings() );
		// Набор глобальных произвольных данных
		$data = ArrayHelper::arrayToJsCode( $this->app->data->getProperties() );

		$js = $jsCore . 'lx.start('
			. $settings . ',' . $data
			. ',`' . $jsBootstrap . '`,`' . $pluginInfo . '`,`' . $jsMain
			. '`);';
		$head = new HtmlHead($pluginData);
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
		if ($this->source) {
			$this->source->invoke('beforeSending');
			$this->source->invoke('beforeSuccessfulSending');
		}
	}

	private function beforeFailedSending()
	{
		if ($this->source) {
			$this->source->invoke('beforeSending');
			$this->source->invoke('beforeFailedSending');
		}
	}
	private function afterSuccessfulSending()
	{
		if ($this->source) {
			$this->source->invoke('afterSuccessfulSending');
			$this->source->invoke('afterSending');
		}
	}
	
	private function afterFailedSending()
	{
		if ($this->source) {
			$this->source->invoke('afterFailedSending');
			$this->source->invoke('afterSending');
		}
	}
}
