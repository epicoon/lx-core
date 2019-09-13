#lx:private;

let prefix = null;
let idCounter = 1;
let list = [];

let autoParentStack = [];

let frontStart = 100;
let frontMap = [];

function __getPrefix() {
	if (prefix === null) {
		var time = Math.floor((new Date()).getTime() * 0.001);
		prefix = ''
			+ lx.Math.decChangeNotation(lx.Math.randomInteger(1, 9999), 62)
			+ lx.Math.decChangeNotation(time, 62)
			+ lx.Math.decChangeNotation(lx.Math.randomInteger(1, 9999), 62);
	}

	return prefix;
}

class WidgetHelper {
	#lx:const
		LXID_ALERTS = lx\WidgetHelper::LXID_ALERTS,
		LXID_TOSTS = lx\WidgetHelper::LXID_TOSTS,
		LXID_BODY = lx\WidgetHelper::LXID_BODY;

	popAutoParent() {
		return autoParentStack.pop();
	}

	resetAutoParent() {
		autoParentStack = [];
	}

	genId() {
		var id = '_' + __getPrefix() + lx.Math.decChangeNotation(idCounter, 62);
		idCounter++;
		return id;
	}

	getIdCounter() {
		return idCounter;
	}

	setIdCounter(val) {
		idCounter = Math.max(idCounter, val);
	}

	register(widget, elem) {
		var lxid = elem ? elem.getAttribute('lxid') : null;
		if (!lxid) {
			lxid = this.genId();
			if (elem) elem.setAttribute('lxid', lxid);
		}

		if (!(lxid in list)) list[lxid] = widget;
		widget.lxid = lxid;
	}

	unregister(lxid) {
		delete list[lxid];
	}

	getByLxid(lxid) {
		return list[lxid];
	}


	/*******************************************************************************************************************
	 * CLIENT ONLY
	 ******************************************************************************************************************/
	#lx:client {
		/**
		 * Возвращает html для элемента, который мог бы быть создан при помощи переданного конфига
		 * */
		getHtmlFor(config) {
			config.parent = null;
			return (new lx.Box(config)).domElem.getHtmlString();
		}

		getElementByLxid(lxid, parent = null) {
			var elem = parent ? parent.getDomElem() : null;
			if (elem) return elem.querySelector("[lxid^='" + lxid + "']");
			else return document.querySelector("[lxid^='" + lxid + "']");
		}

		getByElem(elem) {
			if (!elem || !(elem instanceof Element)) return null;
			return this.getByLxid(elem.getAttribute('lxid'));
		}

		/**
		 * Если присутствует голая верстка, можно вернуть суррогатный lx-объект над элементом, имеющим аттрибут id
		 * */
		getById(id, type = null) {
			var el = document.getElementById(id);
			if (!el) return null;
			var widget = this.getByElem(el);
			if (widget) return widget;

			if (type === null || !type.rise) type = lx.Box;
			return type.rise(el);
		}

		/**
		 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами, имеющими аттрибут name
		 * */
		getByName(name, type = null) {
			if (type === null || !type.rise) type = lx.Box;
			var els = document.getElementsByName(name),
				c = new lx.Collection();
			for (var i = 0, l = els.length; i < l; i++) {
				let el = els[i];
				let widget = this.getByElem(el);
				if (widget) c.add(widget);
				else c.add(type.rise(el));
			}
			return c;
		}

		/**
		 * Если присутствует голая верстка, можно вернуть коллекцию суррогатных lx-объектов над элементами, имеющими определенный css-класс
		 * */
		getByClass(className, type = null) {
			if (type === null || !type.rise) type = lx.Box;
			var els = document.getElementsByClassName(className),
				c = new lx.Collection();
			for (var i = 0, l = els.length; i < l; i++) {
				let el = els[i];
				let widget = this.getByElem(el);
				if (widget) c.add(widget);
				else c.add(type.rise(el));
			}
			return c;
		}

		/**
		 *
		 * */
		getBodyElement() {
			return this.getElementByLxid(self::LXID_BODY);
		}

		/**
		 *
		 * */
		getAlertsElement() {
			return this.getElementByLxid(self::LXID_ALERTS);
		}

		/**
		 *
		 * */
		getTostsElement() {
			return this.getElementByLxid(self::LXID_TOSTS);
		}

		bringToFront(el) {
			if (el.__frontIndex !== undefined) {
				if (el.__frontIndex == frontMap.len - 1) return;
				this.removeFromFrontMap(el);
			}

			el.__frontIndex = frontMap.len;
			el.style('z-index', el.__frontIndex + frontStart);
			frontMap.push(el);
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
			var groupSize = 0,
				forDelete = 0,
				deleted = 0;
			for (var i = 0, l = frontMap.len; i < l; i++) {
				if (!frontMap[i].getDomElem().offsetParent) {
					forDelete++;
					groupSize++;
				} else {
					if (!deleted) continue;
					frontMap[i].__frontIndex -= i - deleted;
					frontMap[i].style('z-index', frontMap[i].__frontIndex + frontStart);

					if (groupSize) {
						frontMap.splice(i, groupSize);
						deleted += groupSize;
						i -= groupSize;
						groupSize = 0;
					}
				}
			}

			if (forDelete == frontMap.len) frontMap = [];
		}
	}
}

lx.WidgetHelper = new WidgetHelper();

Object.defineProperty(lx.WidgetHelper, "autoParent", {
	set: function(val) {
		if (val === null) this.resetAutoParent();
		if (autoParentStack.last() === val) return;
		autoParentStack.push(val);
	},
	get: function() {
		if (!autoParentStack.length) {
			#lx:server{ return Snippet.widget; }
			#lx:client{ return null; }
		}
		return autoParentStack.last();
	}
});
