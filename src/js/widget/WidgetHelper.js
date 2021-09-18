#lx:private;

let autoParentStack = [];

let frontMap = {};

class WidgetHelper {
	#lx:const
		LXID_ALERTS = lx\WidgetHelper::LXID_ALERTS,
		LXID_TOSTS = lx\WidgetHelper::LXID_TOSTS,
		LXID_BODY = lx\WidgetHelper::LXID_BODY;

	set autoParent(val) {
		#lx:client {
			if (val === lx.body) return;
		}

		if (val === null) this.resetAutoParent();
		if (this.autoParent !== val) autoParentStack.push(val);
	}

	get autoParent() {
		if (!autoParentStack.length) {
			#lx:server{ return Snippet.widget; }
			#lx:client{ return lx.body; }
		}
		return autoParentStack.lxLast();
	}

	removeAutoParent(val) {
		#lx:client {
			if (val === lx.body) return;
		}

		if (this.autoParent === val) autoParentStack.pop();
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
			var zShift = el.getZShift() || 0;
			var key = 's' + zShift;
			if (frontMap[key] === undefined) frontMap[key] = [];

			if (el.__frontIndex !== undefined) {
				if (el.__frontIndex == frontMap[key].len - 1) return;
				this.removeFromFrontMap(el);
			}

			var map = frontMap[key];

			if (el.getDomElem() && el.getDomElem().offsetParent) {
				el.__frontIndex = map.len;
				el.style('z-index', el.__frontIndex + el.getZShift());
				map.push(el);
			}
		}

		removeFromFrontMap(el) {
			if (el.__frontIndex === undefined) return;

			var zShift = el.getZShift() || 0;
			var key = 's' + zShift;
			if (frontMap[key] === undefined) frontMap[key] = [];
			var map = frontMap[key];

			for (var i = el.__frontIndex + 1, l = map.len; i < l; i++) {
				map[i].__frontIndex = i - 1;
				map[i].style('z-index', i - 1 + el.getZShift());
			}
			map.splice(el.__frontIndex, 1);
		}

		checkFrontMap() {
			var shown = 0,
				newFrontMap = {};
			for (var key in frontMap) {
				var map = frontMap[key];
				var newMap = [];
				for (var i = 0, l = map.len; i < l; i++) {
					if (map[i].getDomElem() && map[i].getDomElem().offsetParent) {
						var elem = map[i];
						elem.__frontIndex = shown;
						elem.style('z-index', map[i].__frontIndex + elem.getZShift());
						newMap.push(elem);
						shown++;
					}
				}
				newFrontMap[key] = newMap;
			}

			frontMap = newFrontMap;
		}
	}
}

lx.WidgetHelper = new WidgetHelper();
