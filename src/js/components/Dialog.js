Object.defineProperty(lx, 'Dialog', {
	//todo реализовать кэширование
	get: function() {
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
		};

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
		};

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
		};

		/**
		 * Функция посылки запроса к файлу на сервере
		 * method  - тип запроса: GET или POST
		 * path    - путь к файлу
		 * args    - аргументы вида a=1&b=2&c=3...
		 * headers - заголовки в виде объекта
		 * success - функция-обработчик ответа от сервера
		 * */
		function sendRequest(method, path, args, headers, success, waiting, error, isAjax) {
			// Создаём запрос
			var request = createRequest();

			// Проверяем существование запроса еще раз
			if (!request) {
				return /*todo неудача*/;
			}

			// Если требуется сделать GET-запрос, сформируем путь с аргументами
			if (method.toLowerCase() == "get") {
				args = requestParamsToString(args);
				if (args != '') path += '?' + args;
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

			// Обертка при успешном ответе - он может нести системную информацию вместо полезного ответа
			function successWrap(request) {
				var response = request.response;

				var alerts = response.match(/^<lx-alert>[\w\W]*<\/lx-alert>/);
				if (alerts) {
					alerts = alerts[0];
					response = response.replace(alerts, '');
					alerts = alerts.replace(/<lx-alert>/g, '');
					alerts = alerts.replace(/<\/lx-alert>/g, '');
					lx.Alert(alerts);
				}

				// Передаем управление обработчику пользователя
				result = JSON.parse(response);
				callHandler(success, [result, request]);
			}

			// Назначаем пользовательский обработчик
			request.onreadystatechange = function() {
				// Если обмен данными еще не завершен
				if (request.readyState != 4) {
					// Оповещаем пользователя о загрузке
					callHandler(waiting);
					return;
				}

				if (request.status == 200) {
					successWrap(request);
				} else {
					// Оповещаем пользователя о произошедшей ошибке
					callHandler(error, [request]);
				}
			};

			// Инициализируем соединение
			request.open(method, path, true);
			if (isAjax) {
				// Заголовок для сервера, что это AJAX-запрос
				request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
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
		};

		return {
			/**
			 * Отправка запроса на сервер указанным методом
			 * */
			request: function(config) {
				var method = config.method,
					url = config.url,
					headers = config.headers,
					data = config.data,
					success = config.success,
					waiting = config.waiting,
					error = config.error,
					isAjax = [config.isAjax, true].lxGetFirstDefined();
				sendRequest(
					method,
					url,
					data,
					headers,
					success,
					waiting,
					error,
					isAjax  //todo options : { isAjax, withCredentials... }
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
			}
		}
	}
});
