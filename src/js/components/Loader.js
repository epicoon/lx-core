#lx:private;

//todo судя по описанию это должен быть тот же commonContext
// Инкапсулированный контекст зарузки, остающийся единым над рекурсивным созданием экземпляров Loader
let loaderContext = {
	screenDependend: []
};

let loadData = {
	isAjax: null,
	plugins: {},
	snippetsInfo: {},
	snippetTrees: {},
	rootKey: '',
	modulesCode: null,

	necessaryModules: null,
	necessaryScripts: null,
	necessaryCss: null,

	reset: function() {
		this.isAjax = null;
		this.plugins = {};
		this.snippetsInfo = {};
		this.snippetTrees = {};
		this.rootKey = '';
		this.modulesCode = null;
		this.necessaryModules = null;
		this.necessaryScripts = null;
		this.necessaryCss = null;
		SnippetJsNode.list = {};
		SnippetJsNode.counter = 0;
	},

	hasAssets: function() {
		return this.necessaryModules
			|| this.necessaryScripts
			|| this.necessaryCss;
	}
};



function parseInfo(info) {
	var pluginsInfo;

	// Статика приходит строкой, ajax массивом

	// Это статика - ресурсы собраны на стороне сервера
	if (info.isString) {
		loadData.isAjax = false;
		pluginsInfo = info;

		// Достаем код пришедших виджетов
		var matches = pluginsInfo.match(/<modules>([\w\W]*?)<\/modules>/);
		if (matches) loadData.modulesCode = matches[1];

	// Это ajax - если ресурсы пришли, их надо собирать здесь
	} else {
		loadData.isAjax = true;
		pluginsInfo = info.pluginInfo;

		// Определяем какие ресурсы потребуют отдельной дозагрузки
		if (info.scripts) loadData.necessaryScripts = info.scripts;
		if (info.css) loadData.necessaryCss = info.css;
		if (info.modules) loadData.necessaryModules = lx.modules.defineNecessary(info.modules);
	}

	// Парсим инфу по модулям
	var reg = /<plugin (.+?)>/g,
		match;

	var mainKey = null;
	while (match = reg.exec(pluginsInfo)) {
		var key = match[1],
			pluginString = pluginsInfo.match(new RegExp('<plugin '+key+'>([\\w\\W]*?)</plugin '+key+'>'))[1];
		var info = {
			key,
			info: lx.Json.parse(pluginString.match(new RegExp('<mi '+key+'>([\\w\\W]*?)</mi '+key+'>'))[1]),
			bootstrapJs: pluginString.match(new RegExp('<bs '+key+'>([\\w\\W]*?)</bs '+key+'>'))[1],
			snippets: lx.Json.parse(pluginString.match(new RegExp('<bl '+key+'>([\\w\\W]*?)</bl '+key+'>'))[1]),
			mainJs: pluginString.match(new RegExp('<mj '+key+'>([\\w\\W]*?)</mj '+key+'>'))[1]
		};
		loadData.plugins[info.info.anchor] = info;
	}
	loadData.rootKey = '_root_';
}





function createCssTag(href, key) {
	var link  = document.createElement('link');
	link.rel  = 'stylesheet';
	link.type = 'text/css';
	link.href = href;
	link.setAttribute('name', key);
	return link;
}

/**
 * //todo - Была возможность в контексте модуля запускать
 *   теперь модуль создается после загрузки ресурсов
 *   как было - закоменчен одноименный метод в lx.Plugin
 *   может быть стоит переделать
 * */
function createScriptTag(src, key) {
	var script = document.createElement('script');
	script.setAttribute('name', key);
	var onSuccess, onError;
	if (src.isArray) {
		onSuccess = src[1];
		onError = src[2];
		src = src[0];
	}
	script.src = src;
	if (onSuccess) script.onload = onSuccess.bind(script);
	if (onError) script.error = onError.bind(script);
	return script;
}



function loadAssets(callback) {
	// Если нет необходимости в загрузке ресурсов
	if (!loadData.hasAssets()) {
		if (loadData.modulesCode)
			lx.createAndCallFunction('', loadData.modulesCode);
		callback();
		return;
	}

	// Синхронизируем загрузку ресурсов и старт выполнения модуля
	var synchronizer = new lx.Synchronizer(),
		headScriptTags = [],
		cssTags = [];

	// Запрос на догрузку виджетов регистрируется в синхронайзере
	var modulesRequest = null;
	if (loadData.necessaryModules && !loadData.necessaryModules.lxEmpty) {
		modulesRequest = new lx.Request('', loadData.necessaryModules);
		modulesRequest.setHeader('lx-type', 'service');
		modulesRequest.setHeader('lx-service', 'get-modules');
		modulesRequest.success = function(result) {
			if (result) lx.createAndCallFunction('', result);
		};
		synchronizer.register(modulesRequest);
	}

	// script-ресурсы регистрируются в синхронайзере
	if (loadData.necessaryScripts) {
		for (var key in loadData.necessaryScripts) {
			var scripts = loadData.necessaryScripts[key];
			if (scripts.head) {
				for (var i=0; i<scripts.head.len; i++) {
					var tag = createScriptTag(scripts.head[i], key);
					if (!tag) continue;
					synchronizer.register(tag, 'onload', 'onerror');
					headScriptTags.push(tag);
				}
			}
		}
	}

	// css-ресурсы регистрируются в синхронайзере
	if (loadData.necessaryCss) {
		for (var key in loadData.necessaryCss) {
			var css = loadData.necessaryCss[key];
			for (var i=0; i<css.len; i++) {
				var tag = createCssTag(css[i], key);
				if (!tag) continue;
				synchronizer.register(tag, 'onload', 'onerror');
				cssTags.push(tag);
			}
		}
	}

	// Модуль стартанёт после подключения ресурсов
	synchronizer.start(callback);

	// Подтягиваем виджеты
	if (modulesRequest) modulesRequest.send();

	// Подключение скриптов
	//todo
	// document.body.insertBefore(tag, bodyDiv);
	// document.body.appendChild(tag);
	if (headScriptTags.len) {
		var head = document.getElementsByTagName('head')[0];
		for (var i=0; i<headScriptTags.len; i++)
			head.appendChild(headScriptTags[i]);
	}
	
	// Подключение стилей
	if (cssTags.len) {
		var head = document.getElementsByTagName('head')[0];
		for (var i=0; i<cssTags.len; i++)
			head.appendChild(cssTags[i]);
	}
}


function createPlugin(pluginInfo, el, parent, clientCallback) {
	// Создадим экземпляр модуля
	if (!el) {
		lx.body = lx.Box.rise(lx.WidgetHelper.getBodyElement());
		lx.body.key = 'body';
		lx.body.on('scroll', lx.checkDisplay);
		el = lx.body;
	}
	var info = pluginInfo.info;
	if (parent) info.parent = parent;
	info.key = pluginInfo.key;
	var m = lx.Plugin(info, el);

	var bootstrapJs = pluginInfo.bootstrapJs,
		snippets = pluginInfo.snippets,
		mainJs = pluginInfo.mainJs;

	// js-код загрузки модуля
	if (bootstrapJs != '')
		lx.createAndCallFunction('', 'const Plugin=lx.plugins["'+m.key+'"];' + bootstrapJs);

	// Сборка блоков
	loadData.snippetsInfo[m.key] = snippets;
	(new SnippetBuilder(m, null, el, info.rsk)).unpack();
	
	//todo - с ним явно что-то не то
	// Явно обнулим замыкание после использования
	loaderContext = null;
	// Если учитывается режим отображения
	if (m.screenMode !== undefined) {
		// Текущий режим отображения
		m.screenMode = m.idenfifyScreenMode();
		// Организация реакции на смену режима отображения
		for (var i=0, l=loaderContext.screenDependend.length; i<l; i++) {
			let el = loaderContext.screenDependend[i];
			el.constructor.actualizeScreenMode.call(el);
		}
	}

	// Собираем js-код, выполняемый после сборки плагина
	var argsStr = [];
	var args = [];
	var code = 'const Plugin=lx.plugins["'+m.key+'"];lx.WidgetHelper.autoParent=Plugin.root;'
	// Основной js-код плагина
	if (mainJs) code += mainJs;

	// Код сниппетов
	var node = loadData.snippetTrees[m.key];
	var snippetsJs = node.compileCode();

	// Вызываем всё вместе, чтобы у сниппетов был доступ к переменным плагина
	code += snippetsJs[1];
	lx.createAndCallFunction(snippetsJs[0], code, null, snippetsJs[2]);
}

lx.Loader = {
	run: function(info, el, parent, clientCallback) {
		parseInfo(info);

		loadAssets(function() {
			createPlugin(loadData.plugins[loadData.rootKey], el, parent, clientCallback);

			// Вернули контекст в исходное состояние
			loadData.reset();

			if (clientCallback) clientCallback();
		});
	}
};


/**
 * Дерево блоков для сборки пространств имен согласно иерархии
 * */
class SnippetJsNode {
	constructor(builder, parentNode = null) {
		this.snippet = builder.snippet;
		this.js = null;
		this.params = null;
		this.children = [];
		this.key = 'k' + self::keyCounter++;
		self::list[this.key] = this;
		if (parentNode) parentNode.addChild(this);
		else loadData.snippetTrees[builder.plugin.key] = this;
	}

	empty() {
		return !this.children.len;
	}

	addChild(node) {
		this.children.push(node.key);
	}

	child(i) {
		return self::list[this.children[i]];
	}

	compileCode() {
		var argsStr = [];
		var args = [];

		var counter = 0,
			pre = '(function(_w,_p){const Snippet = new lx.Snippet(_w,_p);lx.WidgetHelper.autoParent=_w;_w=_p=undefined;',
			post = 'lx.WidgetHelper.popAutoParent();',
			begin = [],
			end = [];

		function rec(node) {
			if (node.js) {
				args.push(node.snippet);
				args.push(node.params);
				let snippet = '__lxb_' + counter;
				let params = '__lxp_' + counter;
				counter++;
				argsStr.push(snippet);
				argsStr.push(params);
				var js = node.js.replace(/([^;])$/, '$1;');
				let head = pre + js + post;
				let tail = '})('+snippet+','+params+');';
				
				if (node.empty()) begin.push(head + tail);
				else {
					begin.push(head);
					end.push(tail);
				}
			}
			for (var i=0, l=node.children.len; i<l; i++) rec(node.child(i));
		}
		rec(this);

		var code = '';
		for (var i=0, l=begin.len; i<l; i++) code += begin[i];
		for (var i=end.len-1; i>=0; i--) code += end[i];
		return [argsStr.join(','), code, args];
	}
}
SnippetJsNode.keyCounter = 0;
SnippetJsNode.list = {};


/**
 * Собирает дерево элементов, запускает события элементов
 * Создается рекурсивно для вложенных блоков
 * */
class SnippetBuilder {
	constructor(plugin, parentBuilder, snippet, infoIndex) {
		this.plugin = plugin;
		this.snippet = snippet;
		this.parentBuilder = parentBuilder;
		this.index = infoIndex;
		this.elems = [];
		this.onloadElems = [];
		this.node = new SnippetJsNode(this, parentBuilder ? parentBuilder.node : null);
		this.currentElement = 0;
	}

	/**
	 * Применить полученные настройки для корневого блока
	 * */
	applySelf(info) {
		if (info.attrs)
			for (var i in info.attrs)
				this.snippet.domElem.setAttribute(i, info.attrs[i]);

		if (info.classes)
			for (var i=0, l=info.classes.len; i<l; i++)
				this.snippet.domElem.addClass(info.classes[i]);

		if (info.style)
			for (var st in info.style) {
				var val = info.style[st],
					stName = st.replace(/-(\w)/g, function(str, p0) {
						return p0.toUpperCase();
					});
				this.snippet.domElem.style(stName, val);
			}

		if (info.props)
			for (var prop in info.props)
				if (!(prop in this.snippet))
					this.snippet[prop] = info.props[prop];

		if (info.funcs)
			for (var name in info.funcs)
				if (!(name in this.snippet))
					this.snippet[name] = this.snippet.unpackFunction(info.funcs[name]);

		this.snippet.inLoad = true;
		this.snippet.unpackProperties(loaderContext);
		delete this.snippet.inLoad;
		this.snippet.postUnpack();
	}

	/**
	 * Генерация lx-сущностей и воссоздание между ними родительских связей
	 * */
	riseTree(elem, infoLx) {
		for (var i=0,l=elem.getDomElem().children.length; i<l; i++) {
			var node = elem.getDomElem().children[i];

			if (node.getAttribute('lx') === null) continue;

			var info = infoLx[this.currentElement++],
				namespace = info._namespace ? info._namespace : 'lx';

			var namespaceObj = lx.getNamespace(namespace);
			if (!(namespaceObj)) {
				console.error('Widget not found:', namespace + '.' + info.type);
				continue;
			}

			var type = null;

			if (info.type in namespaceObj) type = namespaceObj[info.type];
			else if (namespace == 'lx') type = lx.Box;

			if (type === null) {
				console.error('Widget not found:', namespace + '.' + info.type);
				continue;
			}

			var el = type.rise(node);

			for (var prop in info) {
				if (prop == 'type' || prop == '_namespace' || prop in el) continue;
				el[prop] = info[prop];
			}

			if (info.key) el.key = info.key;

			el.inLoad = true;

			this.elems.push(el);

			el.parent = elem;
			el.domElem.parent = elem;
			elem.registerChild(el);

			this.riseTree(el, infoLx);
		}
	}

	/**
	 * Вызов функций, навешанных на момент загрузки
	 * */
	callOnload(el) {
		var js = el.lxExtract('forOnload');
		if (!js) return;
		for (var i=0; i<js.len; i++) {
			var item = js[i],
				 func,
				 args=null;
			if (item.isArray) {
				func = item[0];
				args = item[1];
			} else func = item;
			func = el.unpackFunction(func);
			if (args === null) func.call(el);
			else {
				if (!args.isArray) args = [args];
				func.apply(el, args);
			}
		}
	}

	unpackContent() {
		// Некоторые свойства требуют распаковки, н-р стратегии позиционирования, функции на обработчиках событий и т.п.
		for (var i=0, l=this.elems.length; i<l; i++) {
			var el = this.elems[i];
			el.unpackProperties(loaderContext);

			// Сборка вложенных блоков
			if (el.ib) {
				(new SnippetBuilder(this.plugin, this, el, el.ib)).unpack();
				delete el.ib;
			}

			// Пришел плагин, собранный в этот элемент
			if (el.pluginAnchor)
				createPlugin(loadData.plugins[el.lxExtract('pluginAnchor')], el, this.plugin);

			delete el.inLoad;
		}

		// Некоторые методы нужно вызвать сразу после распаковки
		for (var i=0, l=this.elems.length; i<l; i++) {
			var el = this.elems[i];

			// постсерверная доработка виджета
			if (lx.unpackType == lx.POSTUNPACK_TYPE_IMMEDIATLY) el.postLoad();
			else if (lx.unpackType == lx.POSTUNPACK_TYPE_FIRST_DISPLAY) el.displayOnce(el.postLoad);
			else if (lx.unpackType == lx.POSTUNPACK_TYPE_ALL_DISPLAY) el.on('displayin', el.postLoad);

			// функции, навешанные на момент загрузки
			this.callOnload(el);
			// если виджет виден - вызов функции на обработчике displayin, в т.ч. displayOnce
			if (el.isDisplay()) el.trigger('displayin');
		}
	}

	unpackJs(info) {
		let clientParams = this.snippet.lxExtract('isp');

		// Если есть js-код для запуска после загрузки блока
		if (info.js) {
			this.node.js = info.js;
			this.node.params = clientParams;
		}
	}

	/**
	 * Основной алгоритм распаковки
	 * */
	unpack() {
		var info = loadData.snippetsInfo[this.plugin.key][this.index];
		if (!info) return;

		if (!this.snippet.isSnippet)
			this.snippet.isSnippet = true;

		if (info.html) {
			// Бухнули верстку в div блока
			this.snippet.domElem.html(info.html);
			// Формирование дерева lx-сущностей
			this.riseTree(this.snippet, info.lx);
		}

		// Блок принес всякие настройки для элемента, в который он выгружается
		if (info.self) this.applySelf(info.self);

		// Допилить содержимое
		if (this.elems.length) this.unpackContent();

		// Вызовы js-кода сниппетов
		this.unpackJs(info);
	}
}
