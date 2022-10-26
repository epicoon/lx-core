#lx:public;

class LoadContext {
	constructor() {
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
			if (info.modules) this.necessaryModules = lx.app.dependencies.defineNecessaryModules(info.modules);
			if (info.page) {
				if (info.page.pluginCss)
					this.necessaryCss = lx.app.dependencies.defineNecessaryCss(info.page.pluginCss);
				if (info.page.scripts)
					this.necessaryScripts = lx.app.dependencies.defineNecessaryScripts(info.page.scripts);
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

	run(el, parent, callback) {
		// Если нет необходимости в загрузке ресурсов
		if (!this.hasAssets()) {
			this.process(el, parent, callback);
			return;
		}

		// Синхронизируем загрузку ресурсов и старт выполнения плагина
		var synchronizer = new lx.RequestSynchronizer();

		var scriptTags = {forHead:[], forBegin:[]},
			cssTags = [];

		// Запрос на догрузку модулей регистрируется в синхронайзере
		if (this.necessaryModules && !this.necessaryModules.lxEmpty()) {
			let modulesRequest = lx.app.loader.loadModules({
				modules: this.necessaryModules,
				immediately: false
			});
			if (modulesRequest) synchronizer.register(modulesRequest);
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
				var tagRequest = new lx.TagResourceRequest(
					this.necessaryCss[i],
					{name: 'plugin_asset'}
				);
				synchronizer.register(tagRequest);
			}
		}

		// Плагин стартанёт после подключения ресурсов
		synchronizer.send().then(()=>{
			this.process(el, parent, callback)
		});
	}

	process(el, parent, callback) {
		this.createPlugin(this.plugins[this.rootKey], el, parent);

		if (this.postScripts.len) {
			var body = document.getElementsByTagName('body')[0];
			this.postScripts.lxForEachRevert(script=>body.appendChild(script));
		}

		callback();
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
			lx.body.key = 'body';
			lx.body.clientBuild();
			el = lx.body;
		}

		let config = pluginInfo.info;
		if (parent) config.parent = parent;
		config.key = pluginInfo.key;
		config.root = el;

		const plugin = lx.app.functionHelper.createAndCallFunctionWithArguments({config}, pluginInfo.mainJs);

		// Run js-code before plugin render
		plugin.beforeRender();

		if (!lx.app.cssManager.isBuilded()) {
			lx.app.cssManager.renderPluginCss(plugin);
			lx.app.cssManager.renderModuleCss({
				presets: [plugin.cssPreset]
			});
		}

		// Render snippets
		this.snippetsInfo[plugin.key] = pluginInfo.snippets;
		(new SnippetLoader(this, plugin, el, config.rsk)).unpack();

		// Note screen mode
		if (plugin.screenMode !== undefined) {
			plugin.screenMode = plugin.idenfifyScreenMode();
		}

		// Run js-code after plugin render but before run
		plugin.beforeRun();

		// Run plugin main js-code
		lx.Rect.setAutoParent(plugin.root);
		plugin.run();

		// Build snippets code
		var code = 'const Plugin=lx.app.plugins.get("' + plugin.key + '");const __plugin__=Plugin;const Snippet=Plugin.root.snippet;';
		var node = this.snippetTrees[plugin.key];
		var snippetsJs = node.compileCode();
		code += snippetsJs[1];
		lx.app.functionHelper.createAndCallFunction(snippetsJs[0], code, null, snippetsJs[2]);

		lx.Rect.removeAutoParent(plugin.root);
		return;
	}

	createPluginByAnchor(anchor, el, parentPlugin) {
		var info = this.plugins[anchor];
		this.createPlugin(info, el, parentPlugin);
	}
};
