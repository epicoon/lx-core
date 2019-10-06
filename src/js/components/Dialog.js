#lx:private;

lx.Dialog = {
	/**
	 * Отправка запроса на сервер указанным методом
	 * */
	request: function(config) {
		return sendRequest(
			config.method,
			config.url,
			config.data,
			config.headers,
			config.success,
			config.waiting,
			config.error
		);
	},

	/**
	 * Отправка запроса на сервер методом GET
	 * */
	get: function(config) {
		config.method = 'get';
		this.request(config);
	},

	/**
	 * Отправка запроса на сервер методом POST
	 * */
	post: function(config) {
		config.method = 'post';
		this.request(config);
	},

	/**
	 * Редирект в пределах сайта по переданному пути
	 * */
	move: function(path) {
		window.location.pathname = path;
	},

	/**
	 * 
	 * */
	requestParamsToString: function(params) {
		return requestParamsToString(params);
	},

	/**
	 * 
	 * */
	requestParamsFromString: function(params) {
		return requestParamsFromString(params);
	},

	/**
	 * 
	 * */
	handlersToConfig: function(handlers) {
		var onSuccess,
			onWaiting,
			onError;
		if (handlers) {
			if (handlers.isFunction || handlers.isArray) {
				onSuccess = handlers;
			} else if (handlers.isObject) {
				onWaiting = handlers.waiting;
				onSuccess = handlers.success;
				onError = handlers.error;
			}
		}
		function initHandler(handlerData) {
			if (!handlerData) return null;
			if (handlerData.isFunction) return handlerData;
			if (handlerData.isArray) return (res)=>handlerData[1].call(handlerData[0], res);
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
};

/**
 * Создает экземпляр XMLHttpRequest
 * */
function createRequest() {
	var Request = false;

	if (window.XMLHttpRequest) {
		//Gecko-совместимые браузеры, Safari, Konqueror
		Request = new XMLHttpRequest();
	} else if (window.ActiveXObject) {
		//Internet explorer
		try {
			Request = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (CatchException) {
			Request = new ActiveXObject("Msxml2.XMLHTTP");
		}
	}

	if (!Request) {
		console.log("Невозможно создать XMLHttpRequest");
	}

	return Request;
}

/**
 * //todo - "first=value&arr[]=foo+bar&arr[]=baz"  + это пробел, наполнение массива
 * */
function requestParamsToString(args) {
	if (!args) return '';
	if (args.isString) return args;
	if (!args.isObject) return '';
	var arr = [];
	var result = '';
	for (var i in args) arr.push(i + '=' + args[i]);
	if (arr.len) result = arr.join('&');
	return result;
}

/**
 * //todo - "first=value&arr[]=foo+bar&arr[]=baz"  + это пробел, наполнение массива
 * */
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

/**
 * Функция посылки запроса к файлу на сервере
 * method  - тип запроса: GET или POST
 * url     - URL запроса
 * args    - аргументы вида a=1&b=2&c=3...
 * headers - заголовки в виде объекта
 * success - функция-обработчик ответа от сервера
 * */
function sendRequest(method, url, args, headers, success, waiting, error) {
	if (!url) url = '';

	// Создаём запрос
	var request = createRequest();
	var handlerMap = {success, waiting, error};

	// Проверяем существование запроса еще раз
	if (!request) {
		return /*todo неудача*/;
	}

	// Если требуется сделать GET-запрос, сформируем путь с аргументами
	if (method.toLowerCase() == "get") {
		args = requestParamsToString(args);
		if (args != '') url += '?' + args;
	}

	function callHandler(handler, args) {
		if (!handler) return;
		if (args !== undefined && !args.isArray) args = [args];
		if (handler.isArray) 
			if (args) handler[1].apply(handler[0], args);
			else handler[1].call(handler[0]);
		else
			if (args) handler.apply(null, args);
			else handler();
	}

	// Обертка при успешном ответе - он может нести отладочную информацию
	function successWrap(request) {
		function findDump(res) {
			if (res.isString) {
				var dump = response.match(/<lx-dump>[\w\W]*<\/lx-dump>/);
				if (dump) {
					dump = dump[0];
					res = res.replace(dump, '');
					dump = dump.replace(/<lx-dump>/, '');
					dump = dump.replace(/<\/lx-dump>/, '');
				}
				return [res, dump];
			} else if (res.isObject) {
				var dump = null;
				if (res.lxdump) {
					dump = res.lxdump;
					delete res.lxdump;
				}
				return [res, dump];
			} else return [res, null];
		}

		var response = request.response;

		// Гостевой флаг
		lx.isGuest = request.getResponseHeader('lx-user-status') !== null;

		// Передаем управление обработчику пользователя
		var contentType = request.getResponseHeader('Content-Type') || '';
		result = contentType.match(/text\/json/)
			? JSON.parse(response)
			: response;

		var resultAndDump = findDump(result);
		result = resultAndDump[0];

		callHandler(handlerMap.success, [result, request]);
		if (resultAndDump[1]) {
			lx.Alert(resultAndDump[1]);
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
			successWrap(request);
		} else {
			// Оповещаем пользователя о произошедшей ошибке
			callHandler(handlerMap.error, [request]);
		}
	};

	// Инициализируем соединение
	request.open(method, url, true);
	if (isAjax(url)) {
		// Заголовок для сервера, что это AJAX-запрос
		request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
		if (lx.__auth) lx.__auth(request);
	}
	for (var name in headers) {
		request.setRequestHeader(name, headers[name]);
	}

	switch (method.toLowerCase()) {
		case 'post':
			//todo - что если надо другим типом отправлять?
			// request.setRequestHeader("Content-Type","application/x-www-form-urlencoded; charset=utf-8");

			// Устанавливаем заголовок
			request.setRequestHeader("Content-Type","application/json; charset=UTF8");

			request.send(lx.Json.encode(args));
			break;
		case 'get':
			// Посылаем нуль-запрос
			request.send(null);
			break;
	}

	return handlerMap;
}

/**
 * Определение типа запроса по URL
 * */
function isAjax(url) {
	var reg = new RegExp('^\w+?:' + '/' + '/');
	return !url.match(reg);
}
