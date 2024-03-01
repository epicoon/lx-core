#lx:namespace lx;
class Dialog extends lx.AppComponent {
	/**
	 * @param config {Object: {
	 * 	   url {String},
	 *     [method == 'get'] {String},
	 *     [data] {Object},
	 *     [headers] {Dict<String>},
	 *     [success] {Function},
	 *     [waiting] {Function},
	 *     [error] {Function},
	 * }}
	 */
	request(config, ignoreEvents = []) {
		return __sendRequest(
			config.method,
			config.url,
			config.data,
			config.headers,
			config.success,
			config.waiting,
			config.error,
			ignoreEvents
		);
	}

	get(config) {
		config.method = 'get';
		this.request(config);
	}

	post(config) {
		config.method = 'post';
		this.request(config);
	}

	/**
	 * Redirect to another page on this web-site
	 */
	move(path) {
		window.location.pathname = path;
	}

	requestParamsToString(params) {
		return requestParamsToString(params);
	}

	requestParamsFromString(params) {
		return requestParamsFromString(params);
	}

	/**
	 * TODO - does not use. Need to be clearyfied is it really deprecated
	 * @deprecated
	 */
	handlersToConfig(handlers) {
		var onSuccess,
			onWaiting,
			onError;
		if (handlers) {
			if (lx.isFunction(handlers) || lx.isArray(handlers)) {
				onSuccess = handlers;
			} else if (lx.isObject(handlers)) {
				onWaiting = handlers.waiting;
				onSuccess = handlers.success;
				onError = handlers.error;
			}
		}
		function initHandler(handlerData) {
			if (!handlerData) return null;
			if (lx.isFunction(handlerData)) return handlerData;
			if (lx.isArray(handlerData)) return (res)=>handlerData[1].call(handlerData[0], res);
			return null;
		}
		var config = {},
			success = initHandler(onSuccess),
			waiting = initHandler(onWaiting),
			error = initHandler(onError);
		if (success) config.success = success;
		if (waiting) config.waiting = waiting;
		if (error) config.error = error;
		return config;
	}
}

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __sendRequest(method, url, data, headers, success, waiting, error, ignoreEvents = []) {
	url = url || '';
	headers = headers || {};
	let calculatedUrl = url;
	if (method.toLowerCase() == 'get') {
		let argsString = requestParamsToString(data);
		if (argsString != '') url += '?' + argsString;
	}

	if (!__isAjax(calculatedUrl)) {
		__sendCorsRequest(method, url, data, headers, success, waiting, error);
		return;
	}

	// Создаём запрос
	const request = createRequest(),
		handlerMap = {success, waiting, error};

	// Проверяем существование запроса еще раз
	if (!request) {
		return /*todo неудача*/;
	}

	// Обертка при успешном ответе - он может нести отладочную информацию
	function successHandlerWrapper(request, handler) {
		var response = request.response;

		lx.app.user.setGuestFlag(request.getResponseHeader('lx-user-status') !== null);

		// Передаем управление обработчику пользователя
		var contentType = request.getResponseHeader('Content-Type') || '';

		#lx:mode-case: dev
		var responseAndDump = __findDump(response), dump;
		response = responseAndDump[0];
		dump = responseAndDump[1];
		#lx:mode-end;

		var result = contentType.match(/text\/json/)
			? JSON.parse(response)
			: response;
		callHandler(handler, [result, request]);

		#lx:mode-case: dev
		if (dump) lx.alert(dump);
		#lx:mode-end;
	}

	function errorHandlerWrapper(request, handler) {
		var response = request.response,
			contentType = request.getResponseHeader('Content-Type') || '';

		#lx:mode-case: dev
		var responseAndDump = __findDump(response), dump;
		response = responseAndDump[0];
		dump = responseAndDump[1];
		#lx:mode-end;

		var result = contentType.match(/text\/json/) ? JSON.parse(response) : response;

		callHandler(handler, [result, request]);

		#lx:mode-case: dev
		if (dump) lx.alert(dump);
		#lx:mode-end;

		switch (result.error_code) {
			case 401:
				if (!ignoreEvents.includes(lx.EVENT_AJAX_REQUEST_UNAUTHORIZED)) lx.app.lifeCycle.trigger(
					lx.EVENT_AJAX_REQUEST_UNAUTHORIZED,
					[result, request, {method, url, data, headers, success, waiting, error}]
				);
				break;
			case 403:
				if (!ignoreEvents.includes(lx.EVENT_AJAX_REQUEST_FORBIDDEN)) lx.app.lifeCycle.trigger(
					lx.EVENT_AJAX_REQUEST_FORBIDDEN,
					[result, request, {method, url, data, headers, success, waiting, error}]
				);
				break;
		}
	}

	// Назначаем пользовательский обработчик
	request.onreadystatechange = function() {
		// Если обмен данными еще не завершен
		if (request.readyState != 4) {
			// Оповещаем пользователя о загрузке
			callHandler(handlerMap.waiting);
			return;
		}

		if (request.status == 200) {
			successHandlerWrapper(request, handlerMap.success);
		} else {
			// Оповещаем пользователя о произошедшей ошибке
			errorHandlerWrapper(request, handlerMap.error);
		}
	};

	// Инициализируем соединение
	request.open(method, calculatedUrl, true);
	if (!ignoreEvents.includes(lx.EVENT_BEFORE_AJAX_REQUEST))
		lx.app.lifeCycle.trigger(lx.EVENT_BEFORE_AJAX_REQUEST, [request]);
	for (var name in headers) {
		request.setRequestHeader(name, headers[name]);
	}

	switch (method.toLowerCase()) {
		case 'post':
			//todo - что если надо другим типом отправлять?
			// request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=utf-8");

			// Устанавливаем заголовок
			request.setRequestHeader('Content-Type','application/json; charset=UTF8');
			request.send(lx.Json.encode(data));
			break;
		case 'get':
			// Посылаем нуль-запрос
			request.send(null);
			break;
	}
}

function __sendCorsRequest(method, url, data, headers, success, waiting, error) {
	let options = {method, mode: 'cors'};
	if (method.toLowerCase() != 'get' && data && !data.lxEmpty()) {
		options.body = lx.Json.encode(data);
		//TODO при добавлении заголовков отправляется OPTIONS-запрос, который обрабатывается nginx
		// headers['Content-Type'] = 'application/json; charset=UTF8';
	}
	// if (!headers.lxEmpty()) options.headers = headers;

	fetch(url, options)
		.then(res=>res.json()).then(res=>callHandler(success, res))
		.catch(res=>callHandler(error, res));
}

/**
 * @return {XMLHttpRequest}
 */
function createRequest() {
	let request = false;

	if (window.XMLHttpRequest) {
		//Gecko-совместимые браузеры, Safari, Konqueror
		request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		//Internet explorer
		try {
			request = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (CatchException) {
			request = new ActiveXObject("Msxml2.XMLHTTP");
		}
	}

	if (!request) {
		console.log("Невозможно создать XMLHttpRequest");
	}

	return request;
}

/**
 * //todo - "first=value&arr[]=foo+bar&arr[]=baz"  + это пробел, наполнение массива
 */
function requestParamsToString(args) {
	if (!args) return '';
	if (lx.isString(args)) return args;
	if (!lx.isObject(args)) return '';
	var arr = [];
	var result = '';
	for (var i in args) arr.push(i + '=' + args[i]);
	if (arr.len) result = arr.join('&');
	return result;
}

/**
 * //todo - "first=value&arr[]=foo+bar&arr[]=baz"  + это пробел, наполнение массива
 */
function requestParamsFromString(str) {
	if (!str || str == '') return {};

	var arr = str.split('&'),
		result = {};
	for (var i=0, l=arr.len; i<l; i++) {
		var boof = arr[i].split('=');
		result[boof[0]] = boof[1];
	}
	return result;
}

function callHandler(handler, args) {
	if (!handler) return;
	if (args !== undefined && !lx.isArray(args)) args = [args];
	if (lx.isArray(handler))
		if (args) handler[1].apply(handler[0], args);
		else handler[1].call(handler[0]);
	else
	if (args) handler.apply(null, args);
	else handler();
}

function __isAjax(url) {
	if (url == '' || url == location.origin) return true;

	let reg = new RegExp('^\\w+?:' + '/' + '/');
	if (!url.match(reg)) return true;

	reg = new RegExp('^\\w+?:' + '/' + '/' + '[^\\/]+');
	return url.match(reg) == location.origin;
}

#lx:mode-case: dev
	function __findDump(res) {
		var dump = res.match(/<pre class="lx-var-dump">[\w\W]*<\/pre>$/);
		if (dump) {
			dump = dump[0];
			res = res.replace(dump, '');
			dump = dump.replace(/^<pre class="lx-var-dump">/, '');
			dump = dump.replace(/<\/pre>$/, '');
		}
		return [res, dump];
	}
#lx:mode-end;
