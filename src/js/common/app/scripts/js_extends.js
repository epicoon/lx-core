/*
Относящееся к структуре
*/
Object.defineProperty(Object.prototype, "lxGetKeys", {
	value: function() {
		var result = [];
		for (var key in this) result.push(key);
		return result;
	}	
});

Object.defineProperty(Object.prototype, "lxClone", {
	value: function() {
		if (!lx.isArray(this) && !lx.isObject(this)) return this;
		var result = (lx.isArray(this)) ? [] : {};
		function rec(from, to) {
			for (var i in from) {
				var val = from[i];
				if (val === null || val === undefined) {
					to[i] = val;
				} else if (lx.isArray(val)) {
					to[i] = [];
					rec(val, to[i]);
				} else if (lx.isObject(val)) {
					to[i] = {};
					rec(val, to[i]);
				} else to[i] = val;
			}
		}
		rec(this, result);
		return result;
	}
});

Object.defineProperty(Object.prototype, "lxCompare", {
	value: function(obj) {
		function rec(left, right) {
			if (!lx.isArray(left) && !lx.isObject(left) && !lx.isArray(right) && !lx.isObject(right)) return left == right;
			if (lx.isArray(left) && !lx.isArray(right)) return false;
			if (lx.isObject(left) && !lx.isObject(right)) return false;

			var leftKeys = left.lxGetKeys()
				rightKeys = right.lxGetKeys();
			if (leftKeys.len != rightKeys.len) return false;
			if (leftKeys.lxDiff(rightKeys).len || rightKeys.lxDiff(leftKeys).len) return false;

			for (var i in left) if (!rec(left[i], right[i])) return false;
			return true;
		}
		return rec(this, obj);
	}
});

// Для примитивов:
// - если отличий нет - вернет false
// - если отличаются, вернет true
// Для объектов:
// - вернет объект с полями, которых нет у объекта, переданного в качеcтве аргумента, либо отличается по значению
Object.defineProperty(Object.prototype, "lxDiff", {
	value: function(obj) {
		var result = {};
		for (var key in this) {
			if (!(key in obj)) {
				result[key] = this[key];
				continue;
			}

			if ((this[key] === undefined && obj[key] === undefined)
				|| (this[key] === null && obj[key] === null)
			) {
				continue;
			}

			if (!this[key].lxCompare(obj[key]))
				result[key] = this[key];
		}
		return result;
	}
});

Object.defineProperty(Object.prototype, "lxGetFirstDefined", {
	value: function(names, defaultValue = undefined) {
		if (!lx.isArray(names)) names = [names];

		for (var i=0, l=names.len; i<l; i++) {
			let name = names[i];
			if (name in this) return this[name];
		}

		return defaultValue;
	}
});

Object.defineProperty(Object.prototype, "lxGetAllProperties", {
	value: function() {
		var obj = this,
			props = [];
		do {
			props = props.concat(Object.getOwnPropertyNames(obj));
		} while (obj = Object.getPrototypeOf(obj));
		return props;
	}
});

Object.defineProperty(Object.prototype, "lxEmpty", {
	value: function() { for (var i in this) return false; return true; }
});

Object.defineProperty(Object.prototype, "lxMerge", {
	value: function(obj, overwrite=false) {
		if (!obj) return this;
		if (lx.isArray(this) && lx.isArray(obj)) {
			for (var i=0, l=obj.length; i<l; i++)
				this.push(obj[i]);
		} else {
			for (var i in obj) {
				if ((lx.isObject(this[i]) && lx.isObject(obj[i]))
					|| (lx.isArray(this[i]) && lx.isArray(obj[i]))
				) {
					this[i].lxMerge(obj[i]);
					continue;
				}
				if (!overwrite && i in this) continue;
				this[i] = obj[i];
			}
		}
		return this;
	}
});

Object.defineProperty(Object.prototype, "lxExtract", {
	value: function(name) {
		if (!(name in this)) return null;
		var res = this[name];
		delete this[name];
		return res;
	}
});

Object.defineProperty(Object.prototype, "lxHasMethod", {
	value: function(name) {
		return (this[name] && lx.isFunction(this[name]));
	}
});

/*
Для определения имен классов и пространств имен
*/
// Для объекта и класса - название пространства имен
Object.defineProperty(Object.prototype, "lxNamespace", {
	value: function() {
		if (lx.isFunction(this) && this.__namespace)
			return this.__namespace;
		if (this.constructor.__namespace)
			return this.constructor.__namespace;
		return '';
	}
});
// Для объекта - имя класса без учета пространства имен
Object.defineProperty(Object.prototype, "lxClassName", {
	value: function() {
		if (this === undefined) return undefined;
		return this.constructor
			? this.constructor.name
			: {}.toString.call(this).slice(8, -1);
	}
});
// Для объекта - имя класса с учетом пространства имен
Object.defineProperty(Object.prototype, "lxFullClassName", {
	value: function() {
		if (this === undefined) return undefined;
		var namespace = this.lxNamespace(),
			name = this.lxClassName();
		if (namespace != '') return namespace + '.' + name;
		return name;
	}
});
// Для класса - имя класса с учетом пространства имен
Object.defineProperty(Function.prototype, "lxFullName", {
	value: function() {
		if (this === undefined) return undefined;
		var namespace = this.lxNamespace(),
			name = this.name;
		if (namespace != '') return namespace + '.' + name;
		return name;
	}
});

Object.defineProperty(String.prototype, "lxRepeat", {
	value: function(multiplier) {
		var buf = '';
		for (var i=0; i<multiplier; i++) {
			buf += this;
		}
		return buf;
	}
});

Object.defineProperty(String.prototype, "lxUcFirst", {
	value: function() {
		if (this == '') return this;
		return this[0].toUpperCase() + this.slice(1);
	}
});


/*
Прокачка массивов
*/
// для IE
if (!Array.prototype.indexOf) {
	Array.prototype.indexOf = function(elt /*, from*/) {
		var len = this.length >>> 0;

		var from = Number(arguments[1]) || 0;
		from = (from < 0)
			? Math.ceil(from)
			: Math.floor(from);
		if (from < 0)
			from += len;

		for (; from < len; from++) {
			if (from in this &&
				this[from] === elt)
			return from;
		}
		return -1;
	};
};

// TODO ???
Object.defineProperty(Array.prototype, "len", {
	get: function() {
		return this.length;
	}
});

Object.defineProperty(Array.prototype, "lxDiff", {
	value: function(arr) {
		var result = [];
		this.forEach(a=>{ if (arr.indexOf(a) == -1) result.push(a); });
		return result;
	}
});

Object.defineProperty(Array.prototype, "lxColumn", {
	value: function(name) {
		var res = [];
		for (var i=0; i<this.length; i++)
			if (this[i] && name in this[i])
				res.push(this[i][name]);
		return res;
	}
});

Object.defineProperty(Array.prototype, "lxLast", {
	value: function() {
		return this[this.len-1];
	}
});

Object.defineProperty(Array.prototype, "lxPushUnique", {
	value: function(elem) {
		if (this.indexOf(elem) == -1) this.push(elem);
	}
});

Object.defineProperty(Array.prototype, "lxRemove", {
	value: function(elem) {
		var index = this.indexOf(elem);
		if (index == -1) return false;
		this.splice(index, 1);
		return true;
	}
});

Object.defineProperty(Array.prototype, "lxForEachRevert", {
	value: function(func) {
		for (var i=this.len-1; i>=0; i--)
			func.call(this, this[i], i);
	}
});
