<?php

namespace lx;

class Dialog extends Object
{
	use ApplicationToolTrait;

	const REQUEST_TYPE_COMMON = 'common';
	const REQUEST_TYPE_PAGE_LOAD = 'page_load';
	const REQUEST_TYPE_AJAX = 'ajax';
	const REQUEST_TYPE_CORS = 'cors';

	private $_serverName;
	private $_type;
	private $_clientIp;
	private $_clientIpFromProxy;

	private $_url = null;
	private $_route = null;
	private $_headers = null;
	private $_method = null;
	private $_get = null;
	private $_post = null;
	private $_cookie = null;
	private $_location = null;

	public function __construct()
	{
		$this->_serverName = $this->app->getConfig('serverName') ?? $_SERVER['SERVER_NAME'];
		$this->defineType();
		$this->defineClientIp();
	}

	public function url()
	{
		if ($this->_url === null) $this->retrieveUrl();
		return $this->_url;
	}

	public function route()
	{
		if ($this->_route === null) $this->retrieveRoute();
		return $this->_route;
	}

	public function getHeaders()
	{
		if ($this->_headers === null) $this->retrieveHeaders();
		return $this->_headers;
	}

	public function header($name)
	{
		$name = strtoupper($name);
		$name = str_replace('-', '_', $name);
		$headers = $this->getHeaders();
		if (array_key_exists($name, $headers)) return $headers[$name];
		return null;
	}

	public function method()
	{
		if ($this->_method === null) $this->retrieveMethod();
		return $this->_method;
	}

	public function isAjax()
	{
		return $this->_type == self::REQUEST_TYPE_AJAX;
	}

	public function isCors()
	{
		return $this->_type == self::REQUEST_TYPE_CORS;
	}

	/**
	 * Является ли текущий запрос - запросом на загрузку страницы
	 * */
	public function isPageLoad()
	{
		return $this->_type == self::REQUEST_TYPE_PAGE_LOAD;
	}

	public function get($name=null)
	{
		if ($this->_get === null) $this->retrieveGet();

		if ($name === null) {
			return $this->_get;
		}

		if (array_key_exists($name, $this->_get)) return $this->_get[$name];
		return null;
	}

	public function post($name=null)
	{
		if ($this->_post === null) $this->retrievePost();

		if ($name === null) {
			return $this->_post;
		}

		if (array_key_exists($name, $this->_post)) return $this->_post[$name];
		return null;
	}

	public function cookie($data = null)
	{
		if ($this->_cookie === null) $this->retrieveCookie();

		if ($data === null) {
			return $this->_cookie;
		}

		if (is_string($data)) {
			return $this->_cookie->$data;
		} elseif (is_array($data)) {
			foreach ($data as $key => $value) {
				$this->_cookie->$key = $value;
			}
		}
	}

	public function params($name=null)
	{
		if ($this->method() == 'get') return $this->get($name);
		if ($this->method() == 'post') return $this->post($name);
		return [];
	}

	/**
	 * Аналог клиентского window.location в js
	 *	Location
	 *		href:"http://main:80/subplugin?lang=en#sheet?caption=app-ajax"
	 *		hash:"#sheet?caption=app-ajax"
	 *		search:"?lang=en"
	 *		pathname:"/subplugin"
	 *		origin:"http://main:80"
	 *		host:"main:80"
	 *		hostname:"main"
	 *		port:"80"
	 *		protocol:"http:"
	 * В дополнение имеет еще поле 'searchArray' - тот же 'search', но уже распаршенный в массив
	 * */
	public function location()
	{
		if ($this->_location === null) {
			$this->retrieveLocation(
				$_SERVER['REQUEST_SCHEME'] . '://'
				. $_SERVER['SERVER_NAME']
				. ':' . $_SERVER['SERVER_PORT']
				. $_SERVER['REQUEST_URI']
			);
		}

		return $this->_location;
	}

	/**
	 * Метод для преобразования строки URL в массив по типу window.location в js
	 * //todo - сейчас частично парсится, доделать все поля!
	 * //todo - hash сюда все равно не передается, нафиг его видимо
	 * */
	public function urlToLocation($url)
	{
		$result = ['href' => $url];

		$parts = explode('#', $url);
		$result['hash'] = count($parts) > 1 ? '#' . $parts[1] : '';

		$parts = explode('?', $parts[0]);
		if (count($parts) > 1) {
			$result['search'] = '?' . $parts[1];
			$result['searchArray'] = $this->translateGetParams($parts[1]);
		} else {
			$result['search'] = '';
			$result['searchArray'] = [];
		}

		return DataObject::create($result);
	}

	public function translateGetParams($text)
	{
		$result = [];

		$temp = urldecode($text);
		$temp = explode('&', $temp);
		foreach ($temp as $value) {
			$item = explode('=', $value);
			$result[$item[0]] = $item[1];
		}

		return $result;
	}

	public function send($data = null)
	{
		if ($data === null) {
			$type = 'html';
			$content = ob_get_contents();
			if ($content != '') {
				ob_end_clean();
			}
		} else {
			$type = is_string($data) ? 'plane' : 'json';
			$content = $data;
		}

		$dump = \lx::getDump();
		if ($dump != '') {
			if (is_array($content)) {
				$content['lxdump'] = $dump;
			} else {
				if ($this->isAjax()) {
					$content .= '<lx-var-dump>' . $dump . '</lx-var-dump>';
				} else {
					$content = '<pre class="lx-var-dump">' . $dump . '</pre>' . $content;
				}
			}
		}

		if ($this->isCors()) {
			$this->addCorsHeaders();
		}

		if ($this->app->user && $this->app->user->isGuest()) {
			header('lx-user-status: guest');
		}
		header("Content-Type: text/$type; charset=utf-8");
		$content = $type=='json' ? json_encode($content) : $content;
		$this->echo($content);
	}

	public function retrieveAll()
	{
		$this->url();
		$this->route();
		$this->getHeaders();
		$this->method();
		$this->params();
		$this->location();
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	private function echo($data)
	{
		// https://stackoverflow.com/questions/15273570/continue-processing-php-after-sending-http-response
		ignore_user_abort(true);
		set_time_limit(0);
		ob_start(/*'ob_gzhandler'*/);
		echo $data;
		header('Connection: close');
		header('Content-Length: ' . ob_get_length());
		ob_end_flush();
		ob_flush();
		flush();
		if (session_id()) session_write_close();
		fastcgi_finish_request();
	}

	private function addCorsHeaders()
	{
		$corsProcessor = $this->app->corsProcessor;
		if ( ! $corsProcessor) {
			return;
		}

		$requestHeaders = [
			'origin' => $this->header('ORIGIN'),
			'method' => $this->header('Access-Control-Request-Method'),
			'headers' => $this->header('Access-Control-Request-Headers'),
		];

		$headers = $corsProcessor->getHeaders($requestHeaders);
		foreach ($headers as $header) {
			header($header);
		}
	}

	private function defineClientIp()
	{
		$this->_clientIp = $_SERVER['REMOTE_ADDR'];

		$fromProxy = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
		if (filter_var($fromProxy, FILTER_VALIDATE_IP)) {
			$this->_clientIpFromProxy = $fromProxy;
		}
	}

	private function defineType()
	{
		$userAgent = $this->header('USER_AGENT');
		if ( ! $userAgent) {
			$this->_type = self::REQUEST_TYPE_COMMON;
			return;
		}

		$origin = $this->header('ORIGIN');
		if ( ! $origin) {
			$this->_type = self::REQUEST_TYPE_PAGE_LOAD;
			return;
		}

		$serverName = $this->_serverName;
		preg_match('/[^:]+?:\/\/(.+)/', $origin, $matches);
		$originName = $matches[1] ?? null;

		if ($serverName == $originName) {
			$this->_type = self::REQUEST_TYPE_AJAX;
		} else {
			$this->_type = self::REQUEST_TYPE_CORS;
		}
	}

	private function retrieveLocation($url)
	{
		$this->_location = $this->urlToLocation($url);
	}

	private function retrieveUrl()
	{
		$requestUri = '';
		if (isset($_SERVER['REQUEST_URI'])) {
			$requestUri = $_SERVER['REQUEST_URI'];
			if ($requestUri !== '' && $requestUri[0] !== '/') {
				$requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
			}
		} elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) { // IIS
			$requestUri = $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif (isset($_SERVER['ORIG_PATH_INFO'])) { // IIS 5.0 CGI
			$requestUri = $_SERVER['ORIG_PATH_INFO'];
			if (!empty($_SERVER['QUERY_STRING'])) {
				$requestUri .= '?' . $_SERVER['QUERY_STRING'];
			}
		}
		$this->_url = $requestUri;
	}

	private function retrieveRoute()
	{
		$url = $this->url();
		$pos = strpos($url, '?');
		if ($pos !== false) {
			$url = substr($url, 0, $pos);
		}
		$url = urldecode($url);
		if (strlen($url) > 1 && $url{0} == '/') {
			$url = substr($url, 1);
		}
		$this->_route = $url;
	}

	private function retrieveHeaders()
	{
		$this->_headers = [];
		$headers = getallheaders();
		foreach ($headers as $key => $value) {
			$key = strtoupper($key);
			$key = str_replace('-', '_', $key);
			$this->_headers[$key] = $value;
		}
	}

	private function retrieveMethod()
	{
		$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
	}

	private function retrieveGet()
	{
		$get = $_GET;
		if (empty($get)) {
			$url = $this->url();
			$temp = explode('?', $url);
			if (count($temp) > 1) {
				$get = $this->translateGetParams($temp[1]);
			}
		}
		$this->_get = $get ?? [];
		/*
		//todo экранирование спецсимволов
		urlencode
		htmlentities
		htmlspecialchars
		*/
	}

	private function retrievePost()
	{
		$post = $_POST;
		if (empty($post)) {
			$input = file_get_contents('php://input');
			$post = json_decode($input, true);
		}
		$this->_post = $post ?? [];
	}

	private function retrieveCookie()
	{
		$this->_cookie = Cookie::create($_COOKIE);
	}
}
