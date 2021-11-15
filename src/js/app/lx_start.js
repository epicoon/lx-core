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


lx.start = function(settings, modules, jsBootstrap, plugin, jsMain) {
	this.setSettings(settings);

	this.setWatchForKeypress(true);
	this.useElementMoving();
	this.useTimers(true);
	this.Event.add(window, 'resize', __windowOnresize);
	__resetInit();

	// Js-модули
	if (modules && modules != '') lx._f.createAndCallFunction('', modules);

	// Глобальный js-код, выполняемый ДО загрузки корневого плагина
	if (jsBootstrap && jsBootstrap != '') lx._f.createAndCallFunction('', jsBootstrap, this);

	// Запуск загрузчика
	if (plugin) lx.Loader.run(plugin, lx.body);

	// Глобальный js-код, выполняемый ПОСЛЕ загрузки корневого плагина
	if (jsMain && jsMain != '') lx._f.createAndCallFunction('', jsMain, this);

	#lx:mode-case: dev
		__findDump();
	#lx:mode-end;

	// Врубаем поддержку контроля времени
	lx.go([Function("lx.doActions();")]);
};

function __windowOnresize(event) {
	function rec(el) {
		el.trigger('resize', event);

		if (!el.childrenCount) return;

		for (var i=0; i<el.childrenCount(); i++) {
			var child = el.child(i);
			if (!child || !child.getDomElem()) continue;
			rec(child);
		}
	}
	rec(lx.body);
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
