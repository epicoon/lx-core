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
		if (!this.isArray && !this.isObject) return this;
		var result = (this.isArray) ? [] : {};
		function rec(from, to) {
			for (var i in from) {
				var val = from[i];
				if (val === null || val === undefined) {
					to[i] = val;
				} else if (val.isArray) {
					to[i] = [];
					rec(val, to[i]);
				} else if (val.isObject) {
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
			if (!left.isArray && !left.isObject && !right.isArray && !right.isObject) return left == right;
			if (left.isArray && !right.isArray) return false;
			if (left.isObject && !right.isObject) return false;

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
		if (!names.isArray) names = [names];

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

		if (this.isArray && obj.isArray) {
			for (var i=0, l=obj.length; i<l; i++)
				this.push(obj[i]);
		} else {
			for (var i in obj) {
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
		return (this[name] && this[name].isFunction);
	}
});

/*
Для определения имен классов и пространств имен
*/
// Для объекта и класса - название пространства имен
Object.defineProperty(Object.prototype, "lxNamespace", {
	value: function() {
		if (this.isFunction && this.__namespace)
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



/*
Блок для определения типа данных
1. Для lx. - объектов наиболее эффективно проверять через lxClassName():
	var bool = element.lxClassName() == 'Widget';
2. Для массивов/строк/чисел - наиболее эффективны соответствующие isArray/isString/isNumber
*/
Object.defineProperty(Object.prototype, "isNumber", {
	get: function() {
		if (this == undefined) return false;
		if ( this.push != undefined ) return false;
		return ( !isNaN(parseFloat(this)) && isFinite(this) );
	}
});

Object.defineProperty(Object.prototype, "isBoolean", {
	get: function() { return this.constructor === Boolean; }
});

Object.defineProperty(Object.prototype, "isString", {
	get: function() { return this.constructor === String; }
});

Object.defineProperty(Object.prototype, "isArray", {
	get: function() { return this.constructor === Array; }
});

Object.defineProperty(Object.prototype, "isObject", {
	get: function() { return (this.constructor === Object || this.lxClassName() == 'Object'); }
});

Object.defineProperty(Object.prototype, "isFunction", {
	get: function() { return this.constructor === Function; }
});

Object.defineProperty(Object.prototype, "is", {
	value: function(instance) {
		if (instance === undefined) return false;
		if (instance === Number) return this.isNumber;
		if (instance.isString)
			return this.is(window[instance]) || this.is(lx[instance]);
		if (this.constructor)
			return this.constructor === instance;
		return this instanceof instance;
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





// TODO - по методам массивов пройтись - что-то убрать, т.к. есть штатное, что-то сделать с префиксом lx

Object.defineProperty(Array.prototype, "toggle", {
	value: function(elem) {
		var index = this.indexOf(elem);
		if (index == -1) this.push(elem);
		else this.splice(index, 1);
		return this;
	}
});

Object.defineProperty(Array.prototype, "each", {
	value: function(func) {
		for (var i=0, l=this.len; i<l; i++)
			func.call(this, this[i], i);
	}
});

Object.defineProperty(Array.prototype, "eachRevert", {
	value: function(func) {
		for (var i=this.len-1; i>=0; i--)
			func.call(this, this[i], i);
	}
});

Object.defineProperty(Array.prototype, "lxDiff", {
	value: function(arr) {
		var result = [];
		this.each(function(a) { if (arr.indexOf(a) == -1) result.push(a); });
		return result;
	}
});

Object.defineProperty(Array.prototype, "contains", {
	value: function(elem) {
		return this.indexOf(elem) !== -1;
	}
});

Object.defineProperty(Array.prototype, "maxOnRange", {
	value: function(i0, i1) {
		var max = -Infinity;
		for (var i=i0; i<=i1; i++)
			if (this[i] > max) max = this[i];
		return max;
	}
});

Object.defineProperty(Array.prototype, "column", {
	value: function(name) {
		var res = [];
		for (var i=0; i<this.length; i++)
			if (this[i] && name in this[i])
				res.push(this[i][name]);
		return res;
	}
});
