lx.checkDisplay = function(event) {
	this.triggerDisplay(event);

	if (this.setBuildMode) this.setBuildMode(true);
	if (this.childrenCount) for (var i=0; i<this.childrenCount(); i++) {
		var child = this.child(i);
		if (!child || !child.getDomElem()) continue;
		lx.checkDisplay.call(child, event);
	}
	if (this.setBuildMode) this.setBuildMode(false);
}


lx.start = function(settings, modules, moduleNames, plugin) {
	this.setSettings(settings);

	this.setWatchForKeypress(true);
	this.useElementMoving();
	this.useTimers(true);
	this.Event.add(window, 'resize', __windowOnresize);
	__resetInit();

	// Js-модули
	if (modules && modules != '') lx._f.createAndCallFunction('', modules);
	this.actualizeModuleCss({
		modules: moduleNames
	});

	// Запуск загрузчика
	if (plugin) lx.Loader.run(plugin, lx.body);

	#lx:mode-case: dev
		__findDump();
	#lx:mode-end;

	// Врубаем поддержку контроля времени
	lx.go([Function("lx.doActions();")]);
};

lx.actualizeModuleCss = function(config) {
	let modules = config.modules || lx.dependencies.getCurrentModules(),
		presets = config.presets || lx.CssPresetsList.getCssPresets();
	for (let i in modules) {
		let module = modules[i],
			moduleClass = lx.getClassConstructor(module);
		if (!moduleClass || !moduleClass.initCss || lx._f.isEmptyFunction(moduleClass.initCss)) continue;
		for (let j in presets) {
			let preset = presets[j],
				cssName = module + '-' + preset.name;
			if (lx.CssTag.exists(cssName)) continue;
			// const css = new lx.CssTag(cssName, lx.CssTag.POSITION_TOP);
			const css = new lx.CssTag(cssName);
			css.usePreset(preset);
			moduleClass.initCss(css.getContext());
			css.commit();
		}
	}
};

function __windowOnresize(event) {
	lx.body.checkResize(event);
}

function __resetInit() {
	delete lx.setWatchForKeypress;
	delete lx.useElementMoving;
	delete lx.useTimers;
}

#lx:mode-case: dev
	function __findDump() {
		var elems = document.getElementsByClassName('lx-var-dump');
		if (!elems.length) return;
		var elem = elems[0];
		var text = elem.innerHTML;
		elem.offsetParent.removeChild(elem);
		lx.Alert(text);
	}
#lx:mode-end;
