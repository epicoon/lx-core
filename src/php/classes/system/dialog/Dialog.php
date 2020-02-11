<?php

namespace lx;

class Dialog extends Object
{
	use ApplicationToolTrait;

	private $_url = null;
	private $_route = null;
	private $_headers = null;
	private $_method = null;
	private $_get = null;
	private $_post = null;
	private $_cookie = null;
	private $_ajax = false;
	private $_location = null;
	private $_clientIp;

	public function __construct()
	{
		$this->_clientIp = $_SERVER['REMOTE_ADDR'];

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) {
			// сюда попадаем в случае AJAX-запроса
			$this->_ajax = true;
		}


		// var_dump($this->headers());

		/*
		Из браузера прилетает такое:
		<b>array</b> <i>(size=9)</i>
		'COOKIE' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'y-scroll=0; treeState=0%2C4%2C8%2C22; lang=en-EN'</font> <i>(length=48)</i>
		'ACCEPT_LANGUAGE' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7'</font> <i>(length=35)</i>
		'ACCEPT_ENCODING' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'gzip, deflate'</font> <i>(length=13)</i>
		'ACCEPT' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,* / *;q=0.8'</font> <i>(length=85)</i>
		'USER_AGENT' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36'</font> <i>(length=105)</i>
		'UPGRADE_INSECURE_REQUESTS' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'1'</font> <i>(length=1)</i>
		'CACHE_CONTROL' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'max-age=0'</font> <i>(length=9)</i>
		'CONNECTION' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'keep-alive'</font> <i>(length=10)</i>
		'HOST' <font color='#888a85'>=&gt;</font> <small>string</small> <font color='#cc0000'>'lx_loc'</font> <i>(length=6)</i>
		*/
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

	public function clientIP()
	{
		$ip = (isset($_SERVER['REMOTE_ADDR']))
			? $_SERVER['REMOTE_ADDR']
			: 'unknown';
		return $ip;
	}

	//todo какая-то херня
	// public function clientRealIP() {
	// 	$ip = $this->clientIP();

	// 	if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	// 		$entries = split('[, ]', $_SERVER['HTTP_X_FORWARDED_FOR']);
	// 		reset($entries);

	// 		foreach ($entries as $entry) {
	// 			$entry = trim($entry);
	// 			if ( preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$)/", $entry, $ipList) ) {
	// 				$privateIp = [
	// 					'/^0\./',
	// 					'/^127\.0\.0\.1/',
	// 					'/^192\.168\..*/',
	// 					'/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
	// 					'/^10\..*/'
	// 				];

	// 				$foundIp = preg_replace($privateIp, $ip, $ipList[1]);

	// 				if ($ip != $foundIp) {
	// 					$ip = $foundIp;
	// 					break;
	// 				}
	// 			}
	// 		}
	// 	}

	// 	return $ip;
	// }

	public function headers()
	{
		if ($this->_headers === null) $this->retrieveHeaders();
		return $this->_headers;
	}

	public function header($name)
	{
		$name = strtoupper($name);
		$name = str_replace('-', '_', $name);
		$headers = $this->headers();
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
		return $this->_ajax;
	}

	/**
	 * //todo
	 * */
	public function isBrowserRequest()
	{
		return true;
	}

	/**
	 * Является ли текущий запрос - запросом на загрузку страницы
	 * */
	public function isPageLoad()
	{
		return !$this->isAjax() && $this->isBrowserRequest();
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
		$this->headers();
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
		ob_start();
		echo $data;
		header('Connection: close');
		header('Content-Length: ' . ob_get_length());
		ob_end_flush();
		ob_flush();
		flush();
		if (session_id()) session_write_close();
		fastcgi_finish_request();
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
		if (($pos = strpos($url, '?')) !== false)
			$url = substr($url, 0, $pos);
		$url = urldecode($url);
		if (strlen($url) > 1 && $url{0} == '/')
			$url = substr($url, 1);
		$this->_route = $url;
	}

	private function retrieveHeaders()
	{
		$this->_headers = [];
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') !== 0) continue;
			$key = preg_replace('/^HTTP_/', '', $key);
			$this->_headers[$key] = $value;
		}
	}

	private function retrieveMethod()
	{
		$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
	}

	private function retrieveGet()
	{
		$get = [];
		if (empty($_GET)) {
			$url = $this->url();
			$temp = explode('?', $url);
			if (count($temp) > 1) {
				$get = $this->translateGetParams($temp[1]);
			}
		} else $get = $_GET;
		$this->_get = $get;
		if ($this->_get === null) $this->_get = [];
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
		$this->_post = $post;
	}

	private function retrieveCookie()
	{
		$this->_cookie = Cookie::create($_COOKIE);
	}
}
