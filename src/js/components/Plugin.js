#lx:private;

lx.Plugin = function(info, snippet) {
	var plugin = {
		name: info.name,
		clientParams: {},
		instances: {},
		namespaces: [],
		widgetBasicCssList: {},
		root: snippet,

		/**
		 * Проверяет является ли данный модуль основым по отношению к формированию страницы (соответствие текущему url)
		 * */
		isMainContext: function() {
			return this.isMain;
		},

		/**
		 * Удалить плагин - очистить корневой сниппет, удалить из реестра, удалить все связанные данные
		 * */
		del: function() {
			// Удаление вложенных плагинов
			var childPlugins = this.childPlugins(true);
			for (var i=0, l=childPlugins.len; i<l; i++)
				childPlugins[i].del();

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
			delete this.root.plugin;
			this.root.clear();

			// Удаление зависимостей от модулей
			if (this.moduleDependencies) {
				lx.modules.independ(plugin.moduleDependencies);
			}

			// Удаление из списка модулей
			delete lx.plugins[this.key];
		},

		/**
		 * Вернет все дочерние плагины - только непосредственные если передать false (по умолчанию), вообще все если передать true
		 * */
		childPlugins: function(all=false) {
			var result = [];

			for (var i in lx.plugins) {
				let plugin = lx.plugins[i];	
				if (all) {
					if (plugin.haveAncestor(this))
						result.push(plugin);
				} else {
					if (plugin.parent === this)
						result.push(plugin);
				}
			}

			return result;
		},

		/**
		 * Проверяет есть ли переданный модуль в иерархии родительских
		 * */
		haveAncestor: function(plugin) {
			var parent = this.parent;
			while (parent) {
				if (parent === plugin) return true;
				parent = parent.parent;
			}
			return false;
		},

		/**
		 * AJAX-запрос в пределах модуля
		 * */
		ajax: function(url, data={}, handlers=null) {
			var config = lx.Dialog.handlersToConfig(handlers);
			config.data = {params:this.clientParams, data};
			config.url = url;

			var headers = [];
			headers['lx-type'] = 'plugin';
			headers['lx-plugin'] = this.name;
			config.headers = headers;

			lx.Dialog.post(config);
		},

		/**
		 * Запрос ajax - post, без пути, с указанием отвечающего, в формате:
		 * {
		 *	plugin: pluginName,
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
			if (!this.activeRequestList)
				this.activeRequestList = new AjaxGet(this);
			this.activeRequestList.registerActiveUrl(url, handlers, useServer);
		},

		/**
		 * Вызов активного GET AJAX-запроса, если он был зарегистрирован
		 * */
		activeRequest: function(url, data={}) {
			if (this.activeRequestList)
				//todo params не срастается с ними логика - при перезагрузке страницы
				// параметры не сохраняются, а data в хэше остается
				this.activeRequestList.request(url, {params:this.clientParams, data});
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

		/**
		 * Метод для переопределения оформления виджетов для конкретных плагинов
		 */
		getWidgetBasicCss: function(widgetClass) {
			if (widgetClass in this.widgetBasicCssList)
				return this.widgetBasicCssList[widgetClass];
			return false;
		},

		subscribeNamespacedClass: function(namespace, className) {
			//todo
			console.log('Plugin.subscribeNamespacedClass:', namespace, className);
		}
	};

	/**
	 * Распаковка карты режимов отображения и создание функционала их отслеживания
	 * */
	function unpackScreenModes(plugin, screenModes) {
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

		plugin.screenModes = modes;
		plugin.screenMode = '';
		// Модуль определяет по ширине экрана режим отображения
		plugin.idenfifyScreenMode = function() {
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
		plugin.root.on('resize', function() {
			this.plugin.screenMode = this.plugin.idenfifyScreenMode();
		});
	};

	// Вероятность мала, но если ключ уже используется каким-то модулем, который был загружен предыдущими запросами - сгенерим уникальный
	if (info.key in lx.plugins) {
		var key;
		function randKey() {
			return '' +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16) +
				lx.Math.decChangeNotation(lx.Math.randomInteger(0, 255), 16);
		};
		do {
			key = randKey();
		} while (key in lx.plugins);
		plugin.key = key;
	} else plugin.key = info.key;
	lx.plugins[plugin.key] = plugin;

	if (info.parent) plugin.parent = info.parent;
	if (info.main) plugin.isMain = true;
	if (info.params) plugin.clientParams = info.params;
	if (info.screenModes) unpackScreenModes(plugin, info.screenModes);

	if (info.images) plugin.images = info.images;

	// Информация о зависимостях от модулей
	if (info.modep) {
		plugin.moduleDependencies = info.modep;
		lx.modules.depend(plugin.moduleDependencies);
	}
	
	if (info.wgdl) {
		plugin.widgetBasicCssList = info.wgdl;
	}


	Object.defineProperty(plugin, "title", {
		set: function(val) {
			if (this.parent) return;
			document.title = val;
		},
		get: function() {
			return document.title;
		}
	});


	snippet.plugin = plugin;
	return plugin;
};

#lx:require plugin/AjaxGet;
