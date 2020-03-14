#lx:private;

let autoParentStack = [];

let frontStart = 100;
let frontMap = [];

class WidgetHelper {
	#lx:const
		LXID_ALERTS = lx\WidgetHelper::LXID_ALERTS,
		LXID_TOSTS = lx\WidgetHelper::LXID_TOSTS,
		LXID_BODY = lx\WidgetHelper::LXID_BODY;

	set autoParent(val) {
		if (val === null) this.resetAutoParent();
		autoParentStack.push(val);
	}

	get autoParent() {
		if (!autoParentStack.length) {
			#lx:server{ return Snippet.widget; }
			#lx:client{ return null; }
		}
		return autoParentStack.last();
	}

	popAutoParent() {
		return autoParentStack.pop();
	}

	resetAutoParent() {
		autoParentStack = [];
	}

	/*******************************************************************************************************************
	 * CLIENT ONLY
	 ******************************************************************************************************************/
	#lx:client {
		getElementByAttrs(attrs, parent = null) {
			var selector = '';
			for (var name in attrs)
				selector += "[" + name + "^='" + attrs[name] + "']"
			var elem = parent ? parent.getDomElem() : null;
			if (elem) return elem.querySelector(selector);
			return document.querySelector(selector);
		}

		/**
		 * Возвращает html для элемента, который мог бы быть создан при помощи переданного конфига
		 * */
		getHtmlFor(config) {
			config.parent = null;
			return (new lx.Box(config)).domElem.getHtmlString();
		}

		getElementByLxId(id, parent = null) {
			var elem = parent ? parent.getDomElem() : null;
			if (elem) return elem.querySelector("[lxid^='" + id + "']");
			return document.querySelector("[lxid^='" + id + "']");
		}

		getById(id, type = null) {
			if (type === null || !type.rise) type = lx.Box;
			var el = document.getElementById(id);
			let widget = el.__lx || type.rise(el);
			return widget;
		}

		getByName(name, type = null) {
			if (type === null || !type.rise) type = lx.Box;
			var els = document.getElementsByName(name),
				c = new lx.Collection();
			for (var i = 0, l = els.length; i < l; i++) {
				let el = els[i];
				let widget = el.__lx;
				if (widget) c.add(widget);
				else c.add(type.rise(el));
			}
			return c;
		}

		/**
		 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами,
		 * имеющими определенный css-класс
		 * */
		getByClass(className, type = null) {
			if (type === null || !type.rise) type = lx.Box;
			var els = document.getElementsByClassName(className),
				c = new lx.Collection();
			for (var i = 0, l = els.length; i < l; i++) {
				let el = els[i];
				let widget = el.__lx;
				if (widget) c.add(widget);
				else c.add(type.rise(el));
			}
			return c;
		}

		/**
		 *
		 * */
		getBodyElement() {
			return this.getElementByLxId(self::LXID_BODY);
		}

		/**
		 *
		 * */
		getAlertsElement() {
			return this.getElementByLxId(self::LXID_ALERTS);
		}

		/**
		 *
		 * */
		getTostsElement() {
			return this.getElementByLxId(self::LXID_TOSTS);
		}

		bringToFront(el) {
			if (el.__frontIndex !== undefined) {
				if (el.__frontIndex == frontMap.len - 1) return;
				this.removeFromFrontMap(el);
			}

			if (el.getDomElem() && el.getDomElem().offsetParent) {
				el.__frontIndex = frontMap.len;
				el.style('z-index', el.__frontIndex + frontStart);
				frontMap.push(el);
			}
		}

		removeFromFrontMap(el) {
			if (el.__frontIndex !== undefined) {
				for (var i = el.__frontIndex + 1, l = frontMap.len; i < l; i++) {
					frontMap[i].__frontIndex = i - 1;
					frontMap[i].style('z-index', i - 1 + frontStart);
				}
				frontMap.splice(el.__frontIndex, 1);
			}
		}

		checkFrontMap() {
			var shown = 0,
				newMap = [];
			for (var i = 0, l = frontMap.len; i < l; i++) {
				if (frontMap[i].getDomElem() && frontMap[i].getDomElem().offsetParent) {
					var elem = frontMap[i];
					elem.__frontIndex = shown;
					elem.style('z-index', frontMap[i].__frontIndex + frontStart);
					newMap.push(elem);
					shown++;
				}
			}
			frontMap = newMap;
		}
	}
}

lx.WidgetHelper = new WidgetHelper();
