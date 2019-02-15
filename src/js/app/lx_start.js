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
			if (child == null || child.DOMelem == null) continue;
			rec(child);
		}
	}
	rec(lx.body);
}

lx.checkDisplay = function(event) {
	this.triggerDisplay(event);

	if (this.childrenCount) for (var i=0; i<this.childrenCount(); i++) {
		var child = this.child(i);
		if (child == null || child.DOMelem == null) continue;
		lx.checkDisplay.call(child, event);
	}
}


lx.start = function(settings, data, jsBootstrap, module, jsMain) {
	this.setSettings(settings);
	this.data = data;

	this.setWatchForKeypress(true);
	this.useElementMoving();
	this.useTimers(true);
	this.Event.add(window, 'resize', windowOnresize);
	resetInit();

	// Глобальный js-код, выполняемый ДО загрузки корневого модуля
	if (jsBootstrap && jsBootstrap != '') lx.createAndCallFunction('', jsBootstrap, this);

	//todo - выпилить, уехало в lx.Loader
	// создание lx-объекта для основного div-а фреймворка
	// lx.body = lx.Box.rise(document.getElementById('lx'));
	// lx.body.key = 'body';
	// lx.body.on('scroll', lx.checkDisplay);

	// Запуск загрузчика
	if (module) lx.Loader.run(module, lx.body);

	// Глобальный js-код, выполняемый ПОСЛЕ загрузки корневого модуля
	if (jsMain && jsMain != '') lx.createAndCallFunction('', jsMain, this);

	// Врубаем поддержку контроля времени
	lx.go([Function("lx.doActions();")]);
};
