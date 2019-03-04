(function() {

lx.modules = {};

lx.Module = function(info, block) {
	var module = {
		name: info.name,
		data: {},
		namespaces: [],
		handlersList: [],
		root: block,

		/**
		 * Проверяет является ли данный модуль основым по отношению к формированию страницы (соответствие текущему url)
		 * */
		isMainContext: function() {
			return this.isMain;
		},

		/**
		 * Удалить модуль - очистить корневой блок, удалить из реестра, удалить все связанные данные
		 * */
		del: function() {
			// Удаление вложенных модулей
			var children = this.childModules(true);
			for (var i=0, l=children.len; i<l; i++)
				children[i].del();

			// Клиентский метод очистки
			if (this.destruct) this.destruct();

			// Удаление пространств имен, созданных модулем
			for (var i=0, l=this.namespaces.len; i<l; i++)
				delete window[this.namespaces[i]];

			// Удаление ресурсов
			var assets = document.getElementsByName(this.key);
			for (var i=assets.length-1; i>=0; i--) {
				var asset = assets[i];
				asset.parentNode.removeChild(asset);
			}

			// Очистка элемента, который был для модуля корневым
			delete this.root.module;
			this.root.clear();

			// Удаление зависимостей от виджетов
			if (this.widgetDependencies) {
				lx.widgets.independ(module.widgetDependencies);
			}

			// Удаление из списка модулей
			delete lx.modules[this.key];
		},

		/**
		 * Вернет все дочерние модули - только непосредственные если передать false (по умолчанию), вообще все если передать true
		 * */
		childModules: function(all=false) {
			var result = [];

			for (var i in lx.modules) {
				let module = lx.modules[i];	
				if (all) {
					if (module.haveAncestor(this))
						result.push(module);
				} else {
					if (module.parent === this)
						result.push(module);
				}
			}

			return result;
		},

		/**
		 * Проверяет есть ли переданный модуль в иерархии родительских
		 * */
		haveAncestor: function(module) {
			var parent = this.parent;
			while (parent) {
				if (parent === module) return true;
				parent = parent.parent;
			}
			return false;
		},

		/**
		 * AJAX-запрос в пределах модуля
		 * */
		ajax: function(url, data={}, handlers=null) {
			var headers = [];
			headers['lx-type'] = 'module';
			headers['lx-module'] = this.name;

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

			//todo для такого запуска функций можно и метод, или класс выделить
			function initHandler(handlerData) {
				if (!handlerData) return null;
				if (handlerData.isFunction) return handlerData;
				if (handlerData.isArray) return (res)=>handlerData[1].call(handlerData[0], res);
				return null;
			}

			var success = initHandler(onSuccess),
				waiting = initHandler(onWaiting),
				error = initHandler(onError),
				config = {url, data, headers};
			if (success) config.success = success;
			if (waiting) config.waiting = waiting;
			if (error) config.error = error;
			lx.Dialog.post(config);
		},

		/**
		 * Запрос ajax - post, без пути, с указанием отвечающего, в формате:
		 * {
		 *	module: moduleName,
		 *	respondent: string,
		 *	data: Object
	 	 * }
		 * */
		callToRespondent: function(__respondent__, data, handlers) {
			this.ajax('', {__respondent__, data}, handlers);
		},

		/**
		 * Регистрация активного GET AJAX-запроса, который будет актуализировать состояние url
		 * */
		registerActiveRequest: function(url, handlers, useServer=true) {
			if (!this.isMainContext()) return;

			if (!this.activeRequestList)
				this.activeRequestList = new AjaxGet(this);
			this.activeRequestList.registerActiveUrl(url, handlers, useServer);
		},

		/**
		 * Вызов активного GET AJAX-запроса, если он был зарегистрирован
		 * */
		activeRequest: function(url, data={}) {
			if (!this.isMainContext() || !this.activeRequestList) return;

			this.activeRequestList.request(url, data);
		},

		// todo селекторы
		get: function(path) {
			return this.root.get(path);
		},

		// поиск по ключу виджетов любого уровня вложенности
		find: function(key, all) {
			var c = this.root.find(key, all);
			if (this.root.key == key) c.add(this.root);
			if (c.empty) return null;
			if (c.len == 1) return c.at(0);
			return c;
		},

		findOne: function(key, all) {
			var c = this.root.find(key, all);
			if (c instanceof lx.Rect) return c;
			if (this.root.key == key) c.add(this.root);
			if (c.empty) return null;
			return c.at(0);
		},

		getImage: function(name='') {
			return this.images + '/' + name;
		},

		setHandler: function(name, func) {
			this.handlersList[name] = func;
		},

		getHandler: function(name) {
			if (name in this.handlersList)
				return this.handlersList[name];
			return null;			
		},

		decodeFunciton: function(essense) {
			if (essense[0] == '(') return lx.createFunctionByInlineString(essense);
			return this.getHandler(essense);
		},

		// //todo 2/2 - оптимизировать ресурсы - если уже подгружен, просто подписаться, не зругизь заново
		// createScriptTag: function(name) {
		// 	var script = document.createElement('script');
		// 	script.setAttribute('name', this.key);
		// 	var onSuccess, onError;
		// 	if (name.isArray) {
		// 		onSuccess = name[1];
		// 		onError = name[2];
		// 		name = name[0];
		// 	}
		// 	script.src = name;
		// 	if (onSuccess) {
		// 		if (onSuccess.isString) onSuccess = this.decodeFunciton(onSuccess);
		// 		if (!onSuccess) return false;
		// 		script.onload = onSuccess.bind(script);
		// 	}
		// 	if (onError) {
		// 		if (onError.isString) onError = this.decodeFunciton(onError);
		// 		if (!onError) return false;
		// 		script.error = onError.bind(script);
		// 	}
		// 	return script;
		// },

		subscribeNamespacedClass: function(namespace, className) {
			//todo
			console.log('Module.subscribeNamespacedClass:', namespace, className);
		}
	};

	/**
	 * Распаковка карты режимов отображения и создание функционала их отслеживания
	 * */
	function unpackScreenModes(module, screenModes) {
		var modes = [];
		for (var i in screenModes) {
			var item = {
				name: i,
				max: screenModes[i]
			};
			if (item.max == 'inf') item.max = Infinity;
			modes.push(item);
		}
		modes.sort(function(a, b) {
			if (a.max > b.max) return 1;
			if (a.max < b.max) return -1;
			return 0;
		});

		module.screenModes = modes;
		module.screenMode = '';
		// Модуль определяет по ширине экрана режим отображения
		module.idenfifyScreenMode = function() {
			var w = this.root.width('px'),
				mode;

			for (var i=0, l=this.screenModes.length; i<l; i++) {
				if (w <= this.screenModes[i].max) {
					mode = this.screenModes[i].name;
					break;
				}
			}

			return mode;
		};

		// Ресайз корневого блока модуля обновляет режим отображения
		module.root.on('resize', function() {
			this.module.screenMode = this.module.idenfifyScreenMode();
		});
	};

	// Вероятность мала, но если ключ уже используется каким-то модулем, который был загружен предыдущими запросами - сгенерим уникальный
	if (info.key in lx.modules) {
		var key;
		function randKey() {
			return '' +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16);
		};
		do {
			key = randKey();
		} while (key in lx.modules);
		module.key = key;
	} else module.key = info.key;
	lx.modules[module.key] = module;

	if (info.parent) module.parent = info.parent;
	if (info.main) module.isMain = true;
	if (info.data) module.data = info.data;
	if (info.screenModes) unpackScreenModes(module, info.screenModes);

	if (info.images) module.images = info.images;
	if (info.handlers)
		for (var i in info.handlers) {
			var code = info.handlers[i];
			module.handlersList[i] = lx.createFunctionByInlineString(code);
		}

	// Информация о зависимостях от виджетов
	if (info.wgd) {
		module.widgetDependencies = info.wgd;
		lx.widgets.depend(module.widgetDependencies);
	}


	Object.defineProperty(module, "title", {
		set: function(val) {
			if (this.parent) return;
			document.title = val;
		},
		get: function() {
			return document.title;
		}
	});


	block.module = module;
	return module;
};

#lx:require module/AjaxGet;

})();
