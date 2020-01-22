#lx:require app/js_extends;

#lx:client{ window.lx = {}; };
#lx:server{ global.lx = {}; };

#lx:require app/lx_function;
#lx:require widget/;


lx.LEFT = 1;
lx.CENTER = 2;
lx.WIDTH = 2;
lx.RIGHT = 3;
lx.JUSTIFY = 4;
lx.TOP = 5;
lx.MIDDLE = 6;
lx.HEIGHT = 6;
lx.BOTTOM = 7;
lx.VERTICAL = 1;
lx.HORIZONTAL = 2;

Object.defineProperty(lx, "globalContext", {
	get: function() {
		#lx:client{ return window; };
		#lx:server{ return global; };
	}
});

#lx:server {
	lx.log = function(data, category = null) {
		if (data === undefined)
			data = '%%%undefined%%%';
		else if (data === null)
			data = '%%%null%%%';
		else if (data && !data.isString) data = JSON.stringify(data);
		__out__.logList.push({category, data});
	};
};

lx.getFirstDefined = function(...args) {
	for (var i=0, l=args.len; i<l; i++)
		if (args[i] !== undefined) return args[i];
	return undefined;
};

lx.createNamespace = function(namespace, props) {
	var arr = namespace.split(/[.\\]/),
		temp = lx.globalContext;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) temp[arr[i]] = {};
		temp = temp[arr[i]];
	}
	if (props) temp.lxMerge(props);
	return temp;
};

lx.getNamespace = function(namespace) {
	var arr = namespace.isString
		? namespace.split(/[.\\]/)
		: (namespace.isArray ? namespace : null);
	if (!arr) return null;
	if (arr.lxEmpty) return lx.globalContext;

	var temp = lx.globalContext;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) return null;
		temp = temp[arr[i]];
	}
	return temp;
};

lx.getClassConstructor = function(fullClassName) {
	var arr = fullClassName.split(/[.\\]/),
		name = arr.pop(),
		nmsp = lx.getNamespace(arr);
	if (!nmsp) return null;
	if (name in nmsp && nmsp[name].isFunction) return nmsp[name];
	return null;
};

lx.classExists = function(name) {
	return !!lx.getClassConstructor(name);
};

lx.createObject = function(fullClassName, args = null) {
	var arr = fullClassName.isString
		? fullClassName.split(/[.\\]/)
		: (fullClassName.isArray ? fullClassName : null);
	if (!arr) return null;

	var temp = lx.globalContext;
	for (var i=0, l=arr.length; i<l; i++) {
		if (temp[arr[i]] === undefined) return null;

		if (i == l - 1) {
			if (args) {
				return new (Function.prototype.bind.apply(
					temp[arr[i]],
					[null].lxMerge(args)
				));
			} else return new temp[arr[i]]();
		} else temp = temp[arr[i]];
	}

	return null;
};
