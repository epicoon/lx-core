#lx:public;

class LoadContext {
	constructor(task) {
		this.task = task;

		this.isAjax = null;
		this.plugins = {};
		this.snippetsInfo = {};
		this.rootKey = '_root_';
		this.snippetTrees = {};
		this.snippetNodesList = {};
		this.snippetsCounter = 0;

		this.necessaryModules = null;
		this.necessaryScripts = null;
		this.necessaryCss = null;

		this.postScripts = [];
	}

	parseInfo(info) {
		var pluginsInfo,
			rootAttr;

		// Статика приходит строкой, ajax массивом

		// Это статика - ресурсы собраны на стороне сервера
		if (lx.isString(info)) {
			this.isAjax = false;
			pluginsInfo = info;
		// Это ajax - если ресурсы пришли, их надо собирать здесь
		} else {
			info = info.data ? info.data : info;
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
			if (info.attributes) rootAttr = info.attributes;
		}

		// Парсим инфу по плагинам
		var reg = /<plugin (.+?)>/g,
			match;
		while (match = reg.exec(pluginsInfo)) {
			var key = match[1],
				pluginString = pluginsInfo.match(new RegExp('<plugin '+key+'>([\\w\\W]*?)</plugin '+key+'>'))[1];
			var pluginInfo = {
				key,
				info: lx.Json.parse(pluginString.match(new RegExp('<mi '+key+'>([\\w\\W]*?)</mi '+key+'>'))[1]),
				snippets: lx.Json.parse(pluginString.match(new RegExp('<bl '+key+'>([\\w\\W]*?)</bl '+key+'>'))[1]),
				mainJs: pluginString.match(new RegExp('<mj '+key+'>([\\w\\W]*?)</mj '+key+'>'))[1]
			};
			if (pluginInfo.info.anchor == this.rootKey) {
				if (!this.isAjax) pluginInfo.isMain = 1;
				if (rootAttr) {
					if (!pluginInfo.info.attributes) pluginInfo.info.attributes = {};
					pluginInfo.info.attributes.lxMerge(rootAttr, true);
				}
			}
			this.plugins[pluginInfo.info.anchor] = pluginInfo;
		}
	}

	run(el, parent, clientCallback) {
		// Если нет необходимости в загрузке ресурсов
		if (!this.hasAssets()) {
			this.process(el, parent, clientCallback);
			this.task.setCompleted();
			return;
		}

		// Синхронизируем загрузку ресурсов и старт выполнения плагина
		var synchronizer = new lx.RequestSynchronizer();

		var scriptTags = {forHead:[], forBegin:[]},
			cssTags = [];

		// Запрос на догрузку модулей регистрируется в синхронайзере
		var modulesRequest = null;
		if (this.necessaryModules && !this.necessaryModules.lxEmpty()) {
			modulesRequest = new lx.ServiceRequest('get-modules', {
				need: this.necessaryModules,
				have: lx.dependencies.getCurrentModules()
			});
			modulesRequest.onLoad(function(result) {
				if (result) lx._f.createAndCallFunction('', result.data);
			});
			synchronizer.register(modulesRequest);
		}

		// script-ресурсы регистрируются в синхронайзере
		if (this.necessaryScripts) {
			for (var i=0; i<this.necessaryScripts.len; i++) {
				var src = this.necessaryScripts[i];
				src.attributes = {name: 'plugin_asset'};
				var tagRequest = lx.TagResourceRequest.createByConfig(src);
				if (tagRequest.location == 'body-bottom')
					this.postScripts.push(tagRequest);
				else synchronizer.register(tagRequest);
			}
		}

		// css-ресурсы регистрируются в синхронайзере
		if (this.necessaryCss) {
			for (var i=0; i<this.necessaryCss.len; i++) {
				var tagRequest = new lx.TagResourceRequest(this.necessaryCss[i], {name: 'plugin_asset'});
				synchronizer.register(tagRequest);
			}
		}

		// Плагин стартанёт после подключения ресурсов
		synchronizer.send().then(()=>{
			this.process(el, parent, clientCallback)
			this.task.setCompleted();
		});
	}

	process(el, parent, clientCallback) {
		this.createPlugin(this.plugins[this.rootKey], el, parent);

		if (this.postScripts.len) {
			var body = document.getElementsByTagName('body')[0];
			this.postScripts.lxForEachRevert(script=>body.appendChild(script));
		}

		if (clientCallback) clientCallback();
	}

	hasAssets() {
		return !!(this.necessaryModules
			|| this.necessaryScripts
			|| this.necessaryCss);
	}

	getSnippetInfo(plugin, index) {
		return this.snippetsInfo[plugin.key][index];
	}

	createPlugin(pluginInfo, el, parent) {
		// Create plugin instance
		if (!el) {
			lx.body = lx.Box.rise(lx.getBodyElement());
			lx.body.key = 'body';
			lx.body.on('scroll', lx.checkDisplay);
			el = lx.body;
		}
		var info = pluginInfo.info;
		if (parent) info.parent = parent;
		info.key = pluginInfo.key;
		var plugin = new lx.Plugin(info, el);

		var snippets = pluginInfo.snippets,
			mainJs = pluginInfo.mainJs;

		// Run js-code before plugin render
		if (info.beforeRender)
			for (var i=0, l=info.beforeRender.len; i<l; i++) {
				var fCode = lx._f.parseFunctionString(info.beforeRender[i])[1];
				lx._f.createAndCallFunction('', 'const Plugin=lx.plugins["'+plugin.key+'"];' + fCode);
			}

		// Render snippets
		this.snippetsInfo[plugin.key] = snippets;
		(new SnippetLoader(this, plugin, el, info.rsk)).unpack();

		// Note screen mode
		if (plugin.screenMode !== undefined) {
			plugin.screenMode = plugin.idenfifyScreenMode();
		}

		// Start build plugin main js-code
		var argsStr = [];
		var args = [];
		var code = 'const Plugin=lx.plugins["' + plugin.key
			+ '"];const Snippet=Plugin.root.snippet;lx.Rect.setAutoParent(Plugin.root);'

		// Build js-code after plugin render but before run
		if (info.beforeRun) {
			for (var i=0, l=info.beforeRun.len; i<l; i++) {
				var str = lx._f.parseFunctionString(info.beforeRun[i])[1];
				if (str[str.length-1] != ';') str += ';';
				code += str;
			}
		}

		// Finish build plugin main js-code
		if (mainJs) code += mainJs;

		// Build snippets code
		var node = this.snippetTrees[plugin.key];
		var snippetsJs = node.compileCode();
		code += snippetsJs[1];

		code += 'lx.Rect.removeAutoParent(Plugin.root);';
		lx._f.createAndCallFunction(snippetsJs[0], code, null, snippetsJs[2]);
	}

	createPluginByAnchor(anchor, el, parentPlugin) {
		var info = this.plugins[anchor];
		this.createPlugin(info, el, parentPlugin);
	}
};