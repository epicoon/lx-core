(function() {

let autoParentStack = [];

lx.WidgetHelper = {
	popAutoParent: function() {
		return autoParentStack.pop();
	},

	resetAutoParent: function() {
		autoParentStack = [];
	},

	/**
	 * Если присутствует голая верстка, можно вернуть суррогатный lx-объект над элементом, имеющим аттрибут id
	 * */
	getById: function(id, type=null) {
		var el = document.getElementById(id);
		if (!el) return null;
		if (el.lx) return el.lx;

		if (type === null || !type.rise) type = lx.Box;
		return type.rise(el);
	},

	/**
	 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами, имеющими аттрибут name
	 * */
	getByName: function(name, type=null) {
		if (type === null || !type.rise) type = lx.Box;
		var els = document.getElementsByName(name),
			c = new lx.Collection();
		for (var i=0, l = els.length; i<l; i++) {
			let el = els[i];
			if (el.lx) c.add(el.lx);
			else c.add(type.rise(el));
		}
		return c;
	},

	/**
	 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами, имеющими определенный css-класс
	 * */
	getByClass: function(className, type=null) {
		if (type === null || !type.rise) type = lx.Box;
		var els = document.getElementsByClassName(className),
			c = new lx.Collection();
		for (var i=0, l = els.length; i<l; i++) {
			let el = els[i];
			if (el.lx) c.add(el.lx);
			else c.add(type.rise(el));
		}
		return c;
	},

	/**
	 * Возвращает html для элемента, который мог бы быть создан при помощи переданного конфига
	 * */
	getHtmlFor: function(config) {
		config.parent = null;
		return (new lx.Box(config)).DOMelem.outerHTML;
	}
};

Object.defineProperty(lx.WidgetHelper, "autoParent", {
	set: function(val) {
		if (val === null) this.resetAutoParent();
		if (autoParentStack.last() === val) return;
		autoParentStack.push(val);
	},
	get: function() {
		if (!autoParentStack.length) return null;
		return autoParentStack.last();
	}
});

})();