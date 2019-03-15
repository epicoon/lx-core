<?php

namespace lx;

/**
 * Класс для работы с параметрами запроса и формирования ответа
 * */
class Dialog {
	/**
	 * regerg
	 * */
	public $test;

	/**
	 * URL
	 * */
	private $_url = null;
	/**
	 * Путь
	 * */
	private $_route = null;
	private $_headers = null;
	private $_method = null;
	private $_get = null;
	private $_post = null;
	private $_ajax = false;
	private $_location = null;
	private $_clientIp;

	public function __construct() {
		$this->_clientIp = $_SERVER['REMOTE_ADDR'];

		if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
		) {
			// сюда попадаем в случае AJAX-запроса
			$this->_ajax = true;
		}
	}

	/**
	 *
	 * */
	public function url() {
		if ($this->_url === null) $this->retrieveUrl();
		return $this->_url;
	}

	/**
	 *
	 * */
	public function route() {
		if ($this->_route === null) $this->retrieveRoute();
		return $this->_route;
	}

	/**
	 *
	 * */
	public function clientIP() {
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

	/**
	 *
	 * */
	public function headers() {
		if ($this->_headers === null) $this->retrieveHeaders();
		return $this->_headers;
	}

	/**
	 *
	 * */
	public function header($name) {
		$name = strtoupper($name);
		$name = str_replace('-', '_', $name);
		$headers = $this->headers();
		if (array_key_exists($name, $headers)) return $headers[$name];
		return null;
	}

	/**
	 *
	 * */
	public function method() {
		if ($this->_method === null) $this->retrieveMethod();
		return $this->_method;
	}

	/**
	 *
	 * */
	public function isAjax() {
		return $this->_ajax;
	}

	/**
	 *
	 * */
	public function get($name=null) {
		if ($this->_get === null) $this->retrieveGet();

		if ($name === null) {
			return $this->_get;
		}

		if (array_key_exists($name, $this->_get)) return $this->_get[$name];
		return null;
	}

	/**
	 *
	 * */
	public function post($name=null) {
		if ($this->_post === null) $this->retrievePost();

		if ($name === null) {
			return $this->_post;
		}

		if (array_key_exists($name, $this->_post)) return $this->_post[$name];
		return null;
	}

	/**
	 *
	 * */
	public function params($name=null) {
		if ($this->method() == 'get') return $this->get($name);
		if ($this->method() == 'post') return $this->post($name);
		return [];
	}

	/**
	 * Аналог клиентского window.location в js
	 *	Location
	 *		href:"http://main:80/submodule?lang=en#sheet?caption=app-ajax"
	 *		hash:"#sheet?caption=app-ajax"
	 *		search:"?lang=en"
	 *		pathname:"/submodule"
	 *		origin:"http://main:80"
	 *		host:"main:80"
	 *		hostname:"main"
	 *		port:"80"
	 *		protocol:"http:"
	 * В дополнение имеет еще поле 'searchArray' - тот же 'search', но уже распаршенный в массив
	 * */
	public function location() {
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
	public function urlToLocation($url) {
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

	/**
	 *
	 * */
	public function translateGetParams($text) {
		$result = [];

		$temp = urldecode($text);
		$temp = explode('&', $temp);
		foreach ($temp as $value) {
			$item = explode('=', $value);
			$result[$item[0]] = $item[1];
		}

		return $result;
	}

	/**
	 *
	 * */
	public function send($data) {
		echo json_encode($data);
	}

	/**
	 *
	 * */
	public function retrieveAll() {
		$this->url();
		$this->route();
		$this->headers();
		$this->method();
		$this->params();
		$this->location();
	}

	/**
	 *
	 * */
	private function retrieveLocation($url) {
		$this->_location = $this->urlToLocation($url);
	}

	/**
	 *
	 * */
	private function retrieveUrl() {
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

	/**
	 *
	 * */
	private function retrieveRoute() {
		$url = $this->url();
		if (($pos = strpos($url, '?')) !== false)
			$url = substr($url, 0, $pos);
		$url = urldecode($url);
		if (strlen($url) > 1 && $url{0} == '/')
			$url = substr($url, 1);
		$this->_route = $url;
	}

	/**
	 *
	 * */
	private function retrieveHeaders() {
		$this->_headers = [];
		foreach ($_SERVER as $key => $value) {
			if (strpos($key, 'HTTP_') !== 0) continue;
			$key = preg_replace('/^HTTP_/', '', $key);
			$this->_headers[$key] = $value;
		}
	}

	/**
	 *
	 * */
	private function retrieveMethod() {
		$this->_method = strtolower($_SERVER['REQUEST_METHOD']);
	}

	/**
	 *
	 * */
	private function retrieveGet() {
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

	/**
	 *
	 * */
	private function retrievePost() {
		$post = $_POST;
		if (empty($post)) {
			$input = file_get_contents('php://input');
			$post = json_decode($input, true);
		}
		$this->_post = $post;
	}
}
