#lx:namespace lx;
class DomSelector extends lx.AppComponentSettable {
	getBodyElement() {
		return document.querySelector("[lxid^='" + this.settings['body'] + "']");
	}

	getTostsElement() {
		return document.querySelector("[lxid^='" + this.settings['tosts'] + "']");
	}

	getAlertsElement() {
		return document.querySelector("[lxid^='" + this.settings['alerts'] + "']");
	}

	getElementByAttrs(attrs, parent = null) {
		let selector = '';
		for (let name in attrs)
			selector += "[" + name + "^='" + attrs[name] + "']"
		let elem = parent ? parent.getDomElem() : null;
		if (elem) return elem.querySelector(selector);
		return document.querySelector(selector);
	}

	getWidgetById(id, type = null) {
		let el = document.getElementById(id);
		if (!el) return null;
		return el.__lx || __getType(type).rise(el);
	}

	getWidgetsByName(name, type = null) {
		let els = document.getElementsByName(name),
			c = new lx.Collection();
		for (let i = 0, l = els.length; i < l; i++) {
			let el = els[i];
			c.add(el.__lx || __getType(type).rise(el));
		}
		return c;
	}

	/**
	 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами,
	 * имеющими определенный css-класс
	 */
	getWidgetsByClass(className, type = null) {
		let els = document.getElementsByClassName(className),
			c = new lx.Collection();
		for (let i = 0, l = els.length; i < l; i++) {
			let el = els[i];
			c.add(el.__lx || __getType(type).rise(el));
		}
		return c;
	}

	getWidgetByClass(className, type = null) {
		let el = document.getElementsByClassName(className)[0];
		if (!el) return null;
		if (el.__lx) return el.__lx;
		return __getType(type).rise(el);
	}
}

function __getType(type) {
	if (type === null || !type.rise) return lx.Box;
	return type;
}
