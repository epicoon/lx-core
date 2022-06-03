#lx:require plugin/;

#lx:namespace lx;
class Plugin {
	constructor(info, snippetBox) {
		this.core = null;
		this.name = info.name;
		this.attributes =  {};
		this.root = snippetBox;
		this.widgetBasicCssList = {};

		this.destructCallbacks = [];
		this.namespaces = [];
		this.dependencies = {};

		this._keypressManager = null;
		this._eventDispatcher = null;
		this._onFocus = null;
		this._onUnfocus = null;

		__init(this, info);

		this.guiNodes = {};
		this.root.click(__onClick);
		this.init();
	}

	init() {
		// pass
	}

	initGuiNodes(map) {
		for (let name in map) {
			let className = map[name];
			if (lx.isString(className) && !lx.classExists(className)) continue;
			let box = this.findOne(name);
			if (box === null) continue;
			this.guiNodes[name] = (lx.isString(className))
				? lx.createObject(className, [this, box])
				: new className(this, box);
		}
	}

	getGuiNode(name) {
		return this.guiNodes[name];
	}

	set title(val) {
		if (this.isMainContext()) document.title = val;
	}

	get title() {
		return document.title;
	}

	beforeRender() { /* pass */ }
	beforeRun() { /* pass */ }
	run() { /* pass */ }

	get eventDispatcher() {
		if (!this._eventDispatcher)
			this._eventDispatcher = new lx.EventDispatcher();
		return this._eventDispatcher;
	}

	on(eventName, callback) {
		this.eventDispatcher.subscribe(eventName, callback);
	}	

	trigger(eventName, data = {}) {
		if (eventName == 'focus') {
			if (this._onFocus) this._onFocus();
			return;
		}
		if (eventName == 'unfocus') {
			if (this._onUnfocus) this._onUnfocus();
			return;
		}

		var event = new lx.PluginEvent(this, data);
		this.eventDispatcher.trigger(eventName, event);
	}

	focus() {
		lx.focusPlugin(this);
	}

	unfocus() {
		lx.unfocusPlugin(this);
	}

	onFocus(callback) {
		this._onFocus = callback.bind(this);
	}

	onUnfocus(callback) {
		this._onUnfocus = callback.bind(this);
	}

	setKeypressManager(className) {
		let manager = lx.createObject(className);
		manager.setContext({plugin: this});
		manager.run();
		this._keypressManager = manager;
	}

	onKeydown(key, func) {
		lx.onKeydown(key, func, {plugin:this});
	}

	offKeydown(key, func) {
		lx.offKeydown(key, func, {plugin:this});
	}

	onKeyup(key, func) {
		lx.onKeyup(key, func, {plugin:this});
	}

	offKeyup(key, func) {
		lx.offKeyup(key, func, {plugin:this});
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
		lx.unfocusPlugin(this);
		this.root.off('click', __onClick);

		// Удаление вложенных плагинов
		var childPlugins = this.childPlugins(true);
		for (var i=0, l=childPlugins.len; i<l; i++)
			childPlugins[i].del();

		// Удаление хэндлеров клавиатуры
		lx.offKeydown(null, null, {plugin:this});
		lx.offKeyup(null, null, {plugin:this});

		// Коллбэки на удаление
		for (var i=0, l=this.destructCallbacks.len; i<l; i++)
			lx._f.callFunction(this.destructCallbacks[i]);

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
				if (!this.dependencies.modules.includes(moduleNames[i]))
					newForPlugin.push(moduleNames[i]);
		} else newForPlugin = moduleNames;

		if (!newForPlugin.len) return;

		if (!this.dependencies.modules) this.dependencies.modules = [];
		lx.dependencies.promiseModules(
			newForPlugin,
			()=>{
				newForPlugin.forEach(a=>this.dependencies.modules.push(a));
				if (callback) callback();
			}
		);
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
		return self::resolveImage(this.images, name);
	}
	
	getImagesPath(key = 'default') {
		if (key in this.images) return this.images[key] + '/';
		return null;
	}
	
	static resolveImage(map, name) {
		if (name[0] != '@') {
			if (!map['default']) return name;
			return map['default'] + '/' + name;
		}

		var arr = name.match(/^@([^\/]+?)(\/.+)$/);
		if (!arr || !map[arr[1]]) return '';

		return map[arr[1]] + arr[2];
	}

	get cssPreset() {
		return lx.CssPresetsList.getCssPreset(this._cssPreset);
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
	if (info.main || info.isMain) plugin.isMain = true;
	if (info.attributes) plugin.attributes = info.attributes;

	if (info.images) plugin.images = info.images;
	plugin._cssPreset = info.cssPreset;

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

function __onClick() {
	this.plugin.focus();
}
