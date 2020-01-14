#lx:private

let handlersList = {};

/**
 * Можно задать некоторую общую функцию уровня приложения
 * */
lx.setHandler = function(name, func) {
	handlersList[name] = func;
};

/**
 * Получение функции уровня приложения, или метода lx
 * */
lx.getHandler = function(name) {
	if (name in handlersList) return handlersList[name];
	if (name in this && this[name].isFunction) return this[name];
	return null;
};

/**
 *
 * */
lx.callFunction = function(data, args = []) {
	if (data.isFunction) data.apply(null, args);
	else if (data.isArray) data[1].apply(data[0], args);
};

/**
 * По переданным строкам аргументов и кода создает функцию и сразу ее запускает
 * */
lx.createAndCallFunction = function(args, code, context=null, params=[]) {
	var f = lx.createFunction(args, code);
	f.apply(context, params);
};

/**
 * Превращает функцию в строку в формате '(arg1, arg2) => ...function code'
 * */
lx.functionToString = function(func) {
	var funcText = null;
	if (func.isFunction) {
		funcText = func.toString();
		if (funcText.match(/^\s*\(/))
			funcText = funcText.replace(/^\s*\(([^\)]*?)\)\s*=>\s*{\s*/, '($1)=>');
		else
			funcText = funcText.replace(/^\s*function\s*\(([^\)]*?)\)\s*{\s*/, '($1)=>');
		funcText = funcText.replace(/\s*}\s*$/, '');
	} else if (func.isString) funcText = func;
	return funcText;
};

/**
 * Создает функцию из вариантов синтаксиса:
 * '(arg1, arg2) => ...function code'
 * 'function code'
 * 'function (arg1, arg2) { ...function code }'
 * */
lx.stringToFunction = function(str) {
	if (str[0] == '(') {
		var reg = /^\(\s*([\w\W]*?)\s*\)\s*=>\s*{?([\w\W]*?)}?\s*$/,
			arr = reg.exec(str);
		if (!arr.len) return null;
		return lx.createFunction(arr[1], arr[2]);
	}

	if (str.match(/^function/)) {
		var reg = /^function[^(]*\(\s*([\w\W]*?)\s*\)\s*{\s*([\w\W]*)\s*}\s*$/,
			arr = reg.exec(str);
		if (!arr.len) return null;
		return lx.createFunction(arr[1], arr[2]);
	}

	return lx.createFunction(null, str);
};

/**
 * Создать функцию по аргументам и коду
 * */
lx.createFunction = function(args, code) {
	if (code === undefined) return Function(args);
	if (args) return Function(args, code);
	return Function(code);
};
