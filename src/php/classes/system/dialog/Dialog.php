<?php

namespace lx;

/**
 * Class Dialog
 * @package lx
 */
class Dialog extends BaseObject
{
	use ApplicationToolTrait;

	const REQUEST_TYPE_COMMON = 'common';
	const REQUEST_TYPE_PAGE_LOAD = 'page_load';
	const REQUEST_ASSET = 'asset';
	const REQUEST_TYPE_AJAX = 'ajax';
	const REQUEST_TYPE_CORS = 'cors';

	/** @var string */
	private $_serverName;

	/** @var string */
	private $_type;

	/** @var string */
	private $_clientIp;

	/** @var string */
	private $_clientIpFromProxy;

	/** @var string */
	private $_url;

	/** @var string */
	private $_route;

	/** @var array */
	private $_headers;

	/** @var string */
	private $_method;

	/** @var array */
	private $_params;

	/** @var Cookie */
	private $_cookie;

	/** @var DataObject */
	private $_location;

	/**
	 * Dialog constructor.
	 */
	public function __construct()
	{
		$this->_serverName = $this->app->getConfig('serverName') ?? $_SERVER['SERVER_NAME'];
		$this->defineType();
		$this->defineClientIp();
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		if ($this->_url === null) $this->retrieveUrl();
		return $this->_url;
	}

	/**
	 * @return string
	 */
	public function getRoute()
	{
		if ($this->_route === null) $this->retrieveRoute();
		return $this->_route;
	}

	/**
	 * @return array
	 */
	public function getHeaders()
	{
		if ($this->_headers === null) $this->retrieveHeaders();
		return $this->_headers;
	}

	/**
	 * @param string $name
	 * @return string|null
	 */
	public function getHeader($name)
	{
		$name = strtoupper($name);
		$name = str_replace('-', '_', $name);
		$headers = $this->getHeaders();
		if (array_key_exists($name, $headers)) return $headers[$name];
		return null;
	}

	/**
	 * @return string
	 */
	public function getMethod()
	{
		if ($this->_method === null) $this->retrieveMethod();
		return $this->_method;
	}

	/**
	 * @return bool
	 */
	public function isAjax()
	{
		return $this->_type == self::REQUEST_TYPE_AJAX;
	}

	/**
	 * @return bool
	 */
	public function isCors()
	{
		return $this->_type == self::REQUEST_TYPE_CORS;
	}

	/**
	 * @return bool
	 */
	public function isPageLoad()
	{
		return $this->_type == self::REQUEST_TYPE_PAGE_LOAD;
	}

	/**
	 * @return bool
	 */
	public function isAssetLoad()
	{
		return $this->_type == self::REQUEST_ASSET;
	}

	/**
	 * @param string $data
	 * @return Cookie|mixed
	 */
	public function getCookie($data = null)
	{
		if ($this->_cookie === null) $this->retrieveCookie();

		if ($data === null) {
			return $this->_cookie;
		}

		if (is_string($data)) {
			return $this->_cookie->$data;
		}

		return null;
	}

	/**
	 * @param string $name
	 * @return mixed
	 */
	public function getParam($name)
	{
		if ($this->_params === null) {
			$this->retrieveParams();
		}

		if (array_key_exists($name, $this->_params)) {
			return $this->_params[$name];
		}

		return null;
	}

	/**
	 * @return array
	 */
	public function getParams()
	{
		if ($this->_params === null) {
			$this->retrieveParams();
		}

		return $this->_params;
	}

	/**
	 * @return DataObject with parameters: [
	 *     'href' => "http://main:80/subplugin?lang=en",
	 *     'origin' => "http://main:80",
	 *     'protocol' => "http:",
	 *     'host' => "main:80",
	 *     'hostname' => "main",
	 *     'port' => "80",
	 *     'pathname' => "/subplugin",
	 *     'search' => "?lang=en",
	 *     'searchArray' => ['lang' => 'en']
	 * ]
	 */
	public function getLocation()
	{
		if ($this->_location === null) {
			$this->_location = $this->urlToLocation(
				$_SERVER['REQUEST_SCHEME'] . '://'
				. $_SERVER['SERVER_NAME']
				. ':' . $_SERVER['SERVER_PORT']
				. $_SERVER['REQUEST_URI']
			);
		}

		return $this->_location;
	}

	/**
	 * @param string $url
	 * @return DataObject
	 */
	public function urlToLocation($url)
	{
		$result = ['href' => $url];

		$parts = explode('?', $url);
		if (count($parts) > 1) {
			$result['search'] = '?' . $parts[1];
			$result['searchArray'] = $this->translateGetParams($parts[1]);
		} else {
			$result['search'] = '';
			$result['searchArray'] = [];
		}

		$str = $parts[0];
		preg_match('/^([^:]+?:)\/\/([^\/]+?)(\/.+)$/', $str, $matches);

		$result['protocol'] = $matches[1];
		$result['host'] = $matches[2];
		$result['pathname'] = $matches[3] ?? '';
		$result['origin'] = $result['protocol'] . '//' . $result['host'];

		$parts = explode(':', $result['host']);
		$result['hostname'] = $parts[0];
		$result['port'] = $parts[1] ?? '';

		return DataObject::create($result);
	}

	/**
	 * @param string $text
	 * @return array
	 */
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

	/**
	 * @param array|string $data
	 */
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
					$dumpStr = '<pre class="lx-var-dump">' . $dump . '</pre>';
					if (preg_match('/<body>/', $content)) {
						$content = preg_replace('/<body>/', '<body>' . $dumpStr, $content);
					} else {
						$content = "$dumpStr$content";
					}
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
		$content = $type == 'json' ? json_encode($content) : $content;
		$this->echo($content);
	}

	/**
	 * Retireve all dialog components
	 */
	public function retrieveAll()
	{
		$this->getUrl();
		$this->getRoute();
		$this->getHeaders();
		$this->getMethod();
		$this->getParams();
		$this->getLocation();
	}


	/*******************************************************************************************************************
	 * PRIVATE
	 ******************************************************************************************************************/

	/**
	 * @param $data
	 */
	private function echo($data)
	{
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

	/**
	 * Method adds all necessary headers for CORS protocol
	 */
	private function addCorsHeaders()
	{
		$corsProcessor = $this->app->corsProcessor;
		if (!$corsProcessor) {
			return;
		}

		$requestHeaders = [
			'origin' => $this->getHeader('ORIGIN'),
			'method' => $this->getHeader('Access-Control-Request-Method'),
			'headers' => $this->getHeader('Access-Control-Request-Headers'),
		];

		$headers = $corsProcessor->getHeaders($requestHeaders);
		foreach ($headers as $header) {
			header($header);
		}
	}

	/**
	 * Trying to recieve client IP
	 */
	private function defineClientIp()
	{
		$this->_clientIp = $_SERVER['REMOTE_ADDR'];

		$fromProxy = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
		if (filter_var($fromProxy, FILTER_VALIDATE_IP)) {
			$this->_clientIpFromProxy = $fromProxy;
		}
	}

	/**
	 * Definition of request type
	 */
	private function defineType()
	{
		$userAgent = $this->getHeader('USER_AGENT');
		if (!$userAgent) {
			$this->_type = self::REQUEST_TYPE_COMMON;
			return;
		}

		$origin = $this->getHeader('ORIGIN');
		if (!$origin) {
			if (preg_match('/\.(js|css)$/', $this->getUrl())) {
				$this->_type = self::REQUEST_ASSET;
			} else {
				$this->_type = self::REQUEST_TYPE_PAGE_LOAD;
			}
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
		$url = $this->getUrl();
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

	private function retrieveParams()
	{
		$get = $_GET ?? [];
		$url = $this->getUrl();
		$temp = explode('?', $url);
		if (count($temp) > 1) {
			$get += $this->translateGetParams($temp[1]);
		}

		$method = $this->getMethod();
		$post = [];
		if ($method == 'post') {
			$post = $_POST ?? [];
			if (empty($post)) {
				$input = file_get_contents('php://input');
				$post = json_decode($input, true);
			}
		}

		$this->_params = $post + $get;
	}

	private function retrieveCookie()
	{
		$this->_cookie = new Cookie();
	}
}
