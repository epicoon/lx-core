#lx:private

function resetInit() {
	delete lx.setWatchForKeypress;
	delete lx.useElementMoving;
	delete lx.useTimers;
}

function windowOnresize(event) {
	function rec(el) {
		el.trigger('resize', event);

		if (!el.children) return;

		for (var i=0; i<el.childrenCount(); i++) {
			var child = el.child(i);
			if (!child || !child.getDomElem()) continue;
			rec(child);
		}
	}
	rec(lx.body);
}

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


lx.start = function(settings, data, jsBootstrap, plugin, jsMain) {
	this.setSettings(settings);
	this.data = data;

	this.setWatchForKeypress(true);
	this.useElementMoving();
	this.useTimers(true);
	this.Event.add(window, 'resize', windowOnresize);
	resetInit();

	// Глобальный js-код, выполняемый ДО загрузки корневого модуля
	if (jsBootstrap && jsBootstrap != '') lx.createAndCallFunction('', jsBootstrap, this);

	// Запуск загрузчика
	if (plugin) lx.Loader.run(plugin, lx.body);

	// Глобальный js-код, выполняемый ПОСЛЕ загрузки корневого модуля
	if (jsMain && jsMain != '') lx.createAndCallFunction('', jsMain, this);

	// Врубаем поддержку контроля времени
	lx.go([Function("lx.doActions();")]);
};
