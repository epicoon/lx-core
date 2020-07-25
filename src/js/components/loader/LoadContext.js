class LoadContext {
	constructor(task) {
		this.task = task;

		this.isAjax = null;
		this.plugins = {};
		this.snippetsInfo = {};
		this.rootKey = '';
		this.snippetTrees = {};
		this.snippetNodesList = {};
		this.snippetsCounter = 0;

		this.necessaryModules = null;
		this.necessaryScripts = null;
		this.necessaryCss = null;

		this.postScripts = [];
	}

	parseInfo(info) {
		var pluginsInfo;

		// Статика приходит строкой, ajax массивом

		// Это статика - ресурсы собраны на стороне сервера
		if (info.isString) {
			this.isAjax = false;
			pluginsInfo = info;
		// Это ajax - если ресурсы пришли, их надо собирать здесь
		} else {
			this.isAjax = true;
			pluginsInfo = info.pluginInfo;

			// Определяем какие ресурсы потребуют отдельной дозагрузки
			if (info.modules) this.necessaryModules = lx.dependencies.defineNecessaryModules(info.modules);
			if (info.page) {
				if (info.page.css)
					this.necessaryCss = lx.dependencies.defineNecessaryCss(info.page.css);
				if (info.page.scripts)
					this.necessaryScripts = lx.dependencies.defineNecessaryScripts(info.page.scripts);
			}
		}

		// Парсим инфу по модулям
		var reg = /<plugin (.+?)>/g,
			match;

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
			if (!this.isAjax && info.info.anchor == '_root_') info.isMain = 1;
			this.plugins[info.info.anchor] = info;
		}
		this.rootKey = '_root_';
	}

	run(el, parent, clientCallback) {
		// Если нет необходимости в загрузке ресурсов
		if (!this.hasAssets()) {
			this.runCallback(el, parent, clientCallback);
			this.task.setCompleted();
			return;
		}

		// Синхронизируем загрузку ресурсов и старт выполнения модуля
		var synchronizer = new lx.Synchronizer();
		synchronizer.setCallback(()=>{
			this.task.setCompleted();
			this.runCallback(el, parent, clientCallback)
		});

		var scriptTags = {forHead:[], forBegin:[]},
			cssTags = [];

		// Запрос на догрузку модулей регистрируется в синхронайзере
		var modulesRequest = null;
		if (this.necessaryModules && !this.necessaryModules.lxEmpty) {
			modulesRequest = new lx.ServiceRequest('get-modules', this.necessaryModules);
			modulesRequest.success = function(result) {
				if (result) lx.createAndCallFunction('', result);
			};
			synchronizer.register(modulesRequest);
		}

		// script-ресурсы регистрируются в синхронайзере
		if (this.necessaryScripts) {
			for (var i=0; i<this.necessaryScripts.len; i++) {
				var tag = this.createScriptTag(this.necessaryScripts[i]);
				switch (tag[1]) {
					case 'head':
						scriptTags.forHead.push(tag[0]);
						synchronizer.register(tag[0], 'onload', 'onerror');
						break;
					case 'body-begin':
						scriptTags.forBegin.push(tag[0]);
						synchronizer.register(tag[0], 'onload', 'onerror');
						break;
					case 'body-end':
						this.postScripts.push(tag[0]);
						break;
				}
			}
		}

		// css-ресурсы регистрируются в синхронайзере
		if (this.necessaryCss) {
			for (var i=0; i<this.necessaryCss.len; i++) {
				var tag = this.createCssTag(this.necessaryCss[i]);
				if (!tag) continue;
				synchronizer.register(tag, 'onload', 'onerror');
				cssTags.push(tag);
			}
		}

		// Плагин стартанёт после подключения ресурсов
		synchronizer.start();

		// Подтягиваем виджеты
		if (modulesRequest) modulesRequest.send();

		// Подключение скриптов
		this.applyScriptTags(scriptTags);
		
		// Подключение стилей
		if (cssTags.len) {
			var head = document.getElementsByTagName('head')[0];
			for (var i=0; i<cssTags.len; i++)
				head.appendChild(cssTags[i]);
		}
	}

	runCallback(el, parent, clientCallback) {
		this.createPlugin(this.plugins[this.rootKey], el, parent);

		if (this.postScripts.len) {
			var body = document.getElementsByTagName('body')[0];
			this.postScripts.eachRevert(script=>body.appendChild(script));
		}

		if (clientCallback) clientCallback();
	}

	hasAssets() {
		return this.necessaryModules
			|| this.necessaryScripts
			|| this.necessaryCss;
	}

	getSnippetInfo(plugin, index) {
		return this.snippetsInfo[plugin.key][index];
	}

	createPlugin(pluginInfo, el, parent) {
		// Создадим экземпляр плагина
		if (!el) {
			lx.body = lx.Box.rise(lx.WidgetHelper.getBodyElement());
			lx.body.key = 'body';
			lx.body.on('scroll', lx.checkDisplay);
			el = lx.body;
		}
		var info = pluginInfo.info;
		if (parent) info.parent = parent;
		info.key = pluginInfo.key;
		var plugin = new lx.Plugin(info, el);
		if (pluginInfo.isMain) plugin.isMain = 1;

		var bootstrapJs = pluginInfo.bootstrapJs,
			snippets = pluginInfo.snippets,
			mainJs = pluginInfo.mainJs;

		// js-код загрузки плагина
		if (bootstrapJs != '')
			lx.createAndCallFunction('', 'const Plugin=lx.plugins["'+plugin.key+'"];' + bootstrapJs);

		// Сборка сниппетов
		this.snippetsInfo[plugin.key] = snippets;
		(new SnippetLoader(this, plugin, el, info.rsk)).unpack();

		// Если учитывается режим отображения
		if (plugin.screenMode !== undefined) {
			// Текущий режим отображения
			plugin.screenMode = plugin.idenfifyScreenMode();
		}

		// Собираем js-код, выполняемый после сборки плагина
		var argsStr = [];
		var args = [];
		var code = 'const Plugin=lx.plugins["' + plugin.key
			+ '"];const Snippet=Plugin.root.snippet;lx.WidgetHelper.autoParent=Plugin.root;'

		// Если есть код, выполняющийся при загрузке плагина - он выполняется здесь
		if (info.onload) {
			for (var i=0, l=info.onload.len; i<l; i++) {
				var str = lx.parseFunctionString(info.onload[i])[1];
				if (str[str.length-1] != ';') str += ';';
				code += str;
			}
		}

		// Основной js-код плагина
		if (mainJs) code += mainJs;

		// Код сниппетов
		var node = this.snippetTrees[plugin.key];
		var snippetsJs = node.compileCode();

		// Вызываем всё вместе, чтобы у сниппетов был доступ к переменным плагина
		code += snippetsJs[1];
		lx.createAndCallFunction(snippetsJs[0], code, null, snippetsJs[2]);
	}

	createPluginByAnchor(anchor, el, parentPlugin) {
		var info = this.plugins[anchor];
		this.createPlugin(info, el, parentPlugin);
	}

	createCssTag(href) {
		var link  = document.createElement('link');
		link.rel  = 'stylesheet';
		link.type = 'text/css';
		link.href = href;
		link.setAttribute('name', 'plugin_asset');
		return link;
	}

	applyScriptTags(scriptTags) {
		if (scriptTags.forHead.len) {
			var head = document.getElementsByTagName('head')[0];
			scriptTags.forHead.each(script=>head.appendChild(script));
		}

		if (scriptTags.forBegin.len) {
			var body = document.getElementsByTagName('body')[0];
			scriptTags.forBegin.eachRevert(script=>{
				if (body.children.length)
					body.insertBefore(script, body.children[0]);
				else body.appendChild(script);
			});
		}
	}

	createScriptTag(src) {
		var location = src.location || 'head';
		var script = document.createElement('script');
		script.setAttribute('name', 'plugin_asset');
		script.src = src.path;
		if (src.onLoad) script.onload = lx.createFunction(src.onLoad).bind(script);
		if (src.onError) script.error = lx.createFunction(src.onError).bind(script);
		return [script, location];
	}
};
