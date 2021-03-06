#lx:private;

#lx:require AjaxGet;

class Plugin #lx:namespace lx {
	constructor(info, snippet) {
		this.name =  info.name;
		this.attributes =  {};
		this.instances = {};
		this.namespaces = [];
		this.widgetBasicCssList = {};
		this.dependencies = {};
		this.root = snippet;
		this.destructCallbacks = [];

		__init(this, info);
	}

	set title(val) {
		if (this.isMainContext()) document.title = val;
	}

	get title() {
		return document.title;
	}

	/**
	 * Проверяет является ли данный плагин основым по отношению к формированию страницы (соответствие текущему url)
	 */
	isMainContext() {
		return this.isMain;
	}

	onDestruct(callback) {
		this.destructCallbacks.push(callback);
	}

	/**
	 * Удалить плагин - очистить корневой сниппет, удалить из реестра, удалить все связанные данные
	 */
	del() {
		// Удаление вложенных плагинов
		var childPlugins = this.childPlugins(true);
		for (var i=0, l=childPlugins.len; i<l; i++)
			childPlugins[i].del();

		// Коллбэки на удаление
		for (var i=0, l=this.destructCallbacks.len; i<l; i++)
			lx.callFunction(this.destructCallbacks[i]);

		// Клиентский метод очистки
		if (this.destruct) this.destruct();

		// Удаление пространств имен, созданных модулем
		for (var i=0, l=this.namespaces.len; i<l; i++)
			delete window[this.namespaces[i]];

		// Очистка элемента, который был для модуля корневым
		delete this.root.plugin;
		this.root.clear();

		// Удаление зависимостей от ресурсов
		lx.dependencies.independ(this.dependencies);

		// Удаление из списка модулей
		delete lx.plugins[this.key];
	}

	/**
	 * Вернет все дочерние плагины - только непосредственные если передать false (по умолчанию), вообще все если передать true
	 */
	childPlugins(all=false) {
		var result = [];

		for (var i in lx.plugins) {
			let plugin = lx.plugins[i];
			if (all) {
				if (plugin.hasAncestor(this))
					result.push(plugin);
			} else {
				if (plugin.parent === this)
					result.push(plugin);
			}
		}

		return result;
	}

	/**
	 * Проверяет есть ли переданный плагин в иерархии родительских
	 */
	hasAncestor(plugin) {
		var parent = this.parent;
		while (parent) {
			if (parent === plugin) return true;
			parent = parent.parent;
		}
		return false;
	}

	useModule(moduleName, callback = null) {
		this.useModules([moduleName], callback);
	}

	useModules(moduleNames, callback = null) {
		var newForPlugin = [];
		if (this.dependencies.modules) {
			for (var i=0, l=moduleNames.len; i<l; i++)
				if (!this.dependencies.modules.contains(moduleNames[i]))
					newForPlugin.push(moduleNames[i]);
		} else newForPlugin = moduleNames;

		if (!newForPlugin.len) return;

		if (!this.dependencies.modules) this.dependencies.modules = [];
		var forLoad = lx.dependencies.defineNecessaryModules(newForPlugin);
		if (forLoad.len) {
			(new lx.ServiceRequest('get-modules', forLoad)).send().then(result=>{
				if (!result) return;
				lx.createAndCallFunction('', result);
				newForPlugin.each(a=>this.dependencies.modules.push(a));
				lx.dependencies.depend({
					modules: newForPlugin
				});
				if (callback) callback();
			});
		} else {
			newForPlugin.each(a=>this.dependencies.modules.push(a));
			lx.dependencies.depend({
				modules: newForPlugin
			});
			if (callback) callback();
		}
	}

	/**
	 * AJAX-запрос в пределах плагина
	 */
	ajax(respondent, data=[]) {
		return new lx.PluginRequest(this, respondent, data);
	}

	/**
	 * Регистрация активного GET AJAX-запроса, который будет актуализировать состояние url
	 */
	registerActiveRequest(key, respondent, handlers, useServer=true) {
		if (!this.activeRequestList)
			this.activeRequestList = new AjaxGet(this);
		this.activeRequestList.registerActiveUrl(key, respondent, handlers, useServer);
	}

	/**
	 * Вызов активного GET AJAX-запроса, если он был зарегистрирован
	 */
	activeRequest(key, data={}) {
		if (this.activeRequestList)
			this.activeRequestList.request(key, data);
	}

	// todo селекторы
	get(path) {
		return this.root.get(path);
	}

	// поиск по ключу виджетов любого уровня вложенности
	find(key, all) {
		var c = this.root.find(key, all);
		if (this.root.key == key) c.add(this.root);
		if (c.empty) return null;
		if (c.len == 1) return c.at(0);
		return c;
	}

	findOne(key, all) {
		var c = this.root.find(key, all);
		if (c instanceof lx.Rect) return c;
		if (this.root.key == key) c.add(this.root);
		if (c.empty) return null;
		return c.at(0);
	}

	getImage(name) {
		if (name[0] != '@') {
			if (!this.images['default']) return name;
			return this.images['default'] + '/' + name;
		}

		var arr = name.match(/^@([^\/]+?)(\/.+)$/);
		if (!arr || !this.images[arr[1]]) return '';

		return this.images[arr[1]] + arr[2];
	}

	/**
	 * Метод для переопределения оформления виджетов для конкретных плагинов
	 */
	getWidgetBasicCss(widgetClass) {
		if (widgetClass in this.widgetBasicCssList)
			return this.widgetBasicCssList[widgetClass];
		return false;
	}

	subscribeNamespacedClass(namespace, className) {
		//todo
		console.log('Plugin.subscribeNamespacedClass:', namespace, className);
	}
}

function __init(plugin, info) {
	// Вероятность мала, но если ключ уже используется каким-то плагином, который был
	// загружен предыдущими запросами - сгенерим уникальный
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
	if (info.attributes) plugin.attributes = info.attributes;

	if (info.images) plugin.images = info.images;

	// Информация о зависимостях от модулей
	if (info.dep) {
		if (info.dep.m) plugin.dependencies.modules = info.dep.m;
		if (info.dep.c) plugin.dependencies.css = info.dep.c;
		if (info.dep.s) plugin.dependencies.scripts = info.dep.s;
		lx.dependencies.depend(plugin.dependencies);
	}

	if (info.wgdl) {
		plugin.widgetBasicCssList = info.wgdl;
	}

	plugin.root.plugin = plugin;
}
