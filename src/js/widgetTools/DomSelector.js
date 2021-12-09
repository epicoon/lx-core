class DomSelector #lx:namespace lx {
	static getElementByAttrs(attrs, parent = null) {
		var selector = '';
		for (var name in attrs)
			selector += "[" + name + "^='" + attrs[name] + "']"
		var elem = parent ? parent.getDomElem() : null;
		if (elem) return elem.querySelector(selector);
		return document.querySelector(selector);
	}

	static getWidgetById(id, type = null) {
		var el = document.getElementById(id);
		if (!el) return null;
		return el.__lx || __getType(type).rise(el);
	}

	static getWidgetByName(name, type = null) {
		var els = document.getElementsByName(name),
			c = new lx.Collection();
		for (var i = 0, l = els.length; i < l; i++) {
			let el = els[i];
			c.add(el.__lx || __getType(type).rise(el));
		}
		return c;
	}

	/**
	 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами,
	 * имеющими определенный css-класс
	 */
	static getWidgetByClass(className, type = null) {
		var els = document.getElementsByClassName(className),
			c = new lx.Collection();
		for (var i = 0, l = els.length; i < l; i++) {
			let el = els[i];
			c.add(el.__lx || __getType(type).rise(el));
		}
		return c;
	}
}

function __getType(type) {
	if (type === null || !type.rise) return lx.Box;
	return type;
}
