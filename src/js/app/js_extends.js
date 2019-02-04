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

Object.defineProperty(Object.prototype, "lxCopy", {
	value: function() {
		var result = {};
		function rec(to, from) {
			for (var i in from) {
				var val = from[i];
				if (val.isArray) {
					to[i] = [];
					rec(to[i], val);
				} else if (val.isObject) {
					to[i] = {};
					rec(to[i], val);
				} else to[i] = from[i];
			}
		}
		rec(result, this);
		return result;
	}
});

Object.defineProperty(Object.prototype, "getFirstDefined", {
	value: function(names, defaultValue = undefined) {
		if (!names.isArray) names = [names];

		for (var i=0, l=names.len; i<l; i++) {
			let name = names[i];
			if (name in this) return this[name];
		}

		return defaultValue;
	}
});

Object.defineProperty(Object.prototype, "getAllProperties", {
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
	get: function() { for (var i in this) return false; return true; }
});

Object.defineProperty(Object.prototype, "lxMerge", {
	value: function(obj, overwrite=false) {
		if (this.isAssoc || obj.isAssoc) {
			for (var i in obj) {
				if (!overwrite && i in this) continue;
				this[i] = obj[i];
			}
		} else {
			for (var i=0, l=obj.length; i<l; i++)
				this.push(obj[i]);
		}
		return this;
	}
});

Object.defineProperty(Object.prototype, "extract", {
	value: function(name) {
		if (!(name in this)) return null;
		var res = this[name];
		delete this[name];
		return res;
	}
});

Object.defineProperty(Object.prototype, "hasMethod", {
	value: function(name) {
		return (this[name] && this[name].isFunction);
	}
});



/*
Для определения имен классов и пространств имен
*/
// Для объекта и класса - название пространства имен
Object.defineProperty(Object.prototype, "namespace", {
	get: function() {
		if (this.isFunction && this.__namespace)
			return this.__namespace;
		if (this.constructor.__namespace)
			return this.constructor.__namespace;
		return '';
	}
});
// Для объекта - имя класса без учета пространства имен
Object.defineProperty(Object.prototype, "className", {
	get: function() {
		if (this === undefined) return undefined;
		return this.constructor
			? this.constructor.name
			: {}.toString.call(this).slice(8, -1);
	}
});
// Для объекта - имя класса с учетом пространства имен
Object.defineProperty(Object.prototype, "fullClassName", {
	get: function() {
		if (this === undefined) return undefined;
		var namespace = this.namespace,
			name = this.className;
		if (namespace != '') return namespace + '.' + name;
		return name;
	}
});
// Для класса - имя класса с учетом пространства имен
Object.defineProperty(Function.prototype, "fullName", {
	get: function() {
		if (this === undefined) return undefined;
		var namespace = this.namespace,
			name = this.name;
		if (namespace != '') return namespace + '.' + name;
		return name;
	}
});



/*
Блок для определения типа данных
1. Для lx. - объектов наиболее эффективно проверять через className:
	var bool = element.className == 'Widget';
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
	get: function() { return (this.constructor === Object || this.className == 'Object'); }
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

Object.defineProperty(Array.prototype, "len", {
	get: function() { return this.length; }
});

Object.defineProperty(Array.prototype, "last", {
	value: function() { return this[this.len-1]; }
});

Object.defineProperty(Array.prototype, "equalTo", {
	value: function(a) {
		var b = this;
		if (a === b) return true;
		if (a == null || b == null) return false;
		if (a.length != b.length) return false;
		for (var i = 0; i < a.length; ++i) {
			if (a[i] !== b[i]) return false;
		}
		return true;
	}
});

Object.defineProperty(Array.prototype, "pushUnique", {
	value: function(elem) {
		if (this.indexOf(elem) == -1) this.push(elem);
	}
});

Object.defineProperty(Array.prototype, "remove", {
	value: function(elem) {
		var index = this.indexOf(elem);
		if (index == -1) return false;
		this.splice(index, 1);
		return true;
	}
});

Object.defineProperty(Array.prototype, "toggle", {
	value: function(elem) {
		var index = this.indexOf(elem);
		if (index == -1) this.push(elem);
		else this.splice(index, 1);
		return this;
	}
});

Object.defineProperty(Array.prototype, "isAssoc", {
	get: function() {
		return !this.lxEmpty && (!(0 in this) || !this.length);
	}
});
Object.defineProperty(Object.prototype, "isAssoc", {
	get: function() {
		return true;
	}
});

Object.defineProperty(Array.prototype, "getFirstDefined", {
	value: function() {
		if (this.isAssoc) return undefined;
		for (var i=0, l=this.len; i<l; i++) {
			if (this[i] !== undefined) return this[i];
		}
		return undefined;
	}
});

Object.defineProperty(Array.prototype, "each", {
	value: function(func) {
		if (this.isAssoc) for (var key in this)
			func.call(this, this[key], key);
		else for (var i=0, l=this.len; i<l; i++)
			func.call(this, this[i], i);
	}
});

Object.defineProperty(Array.prototype, "eachRevert", {
	value: function(func) {
		for (var i=this.len-1; i>=0; i--)
			func.call(this, this[i], i);
	}
});

Object.defineProperty(Array.prototype, "diff", {
	value: function(arr) {
		var result = [];
		this.each(function(a) { if (arr.indexOf(a) == -1) result.push(a); });
		return result;
	}
});

Object.defineProperty(Array.prototype, "contain", {
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
		for (var i=0; i<this.length; i++) res.push(this[i][name]);
		return res;
	}
});
