#lx:private;

let autoParentStack = [];

let frontStart = 100;
let frontMap = [];

lx.WidgetHelper = {
	popAutoParent: function() {
		return autoParentStack.pop();
	},

	resetAutoParent: function() {
		autoParentStack = [];
	},

	/**
	 *
	 * */
	bringToFront: function(el) {
		if (el.__frontIndex !== undefined) {
			if (el.__frontIndex == frontMap.len - 1) return;
			this.removeFromFrontMap(el);
		}

		el.__frontIndex = frontMap.len;
		el.style('z-index', el.__frontIndex + frontStart);
		frontMap.push(el);
	},

	/**
	 *
	 * */
	removeFromFrontMap: function(el) {
		if (el.__frontIndex !== undefined) {
			for (var i=el.__frontIndex+1, l=frontMap.len; i<l; i++) {
				frontMap[i].__frontIndex = i - 1;
				frontMap[i].style('z-index', i - 1 + frontStart);
			}
			frontMap.splice(el.__frontIndex, 1);
		}
	},

	/**
	 *
	 * */
	checkFrontMap: function() {
		var groupSize = 0,
			forDelete = 0,
			deleted = 0;
		for (var i=0, l=frontMap.len; i<l; i++) {
			if (!frontMap[i].DOMelem.offsetParent) {
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
