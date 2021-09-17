#lx:private

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
	return f.apply(context, params);
};

lx.createAndCallFunctionWithArguments = function(args, code, context=null) {
    code = code.replace(/(^[^{]+?{|}\s*$)/g, '');
    var kstl = '}';
    var argsArr = [];
    var argNamesArr = [];
    for (var name in args) {
    	argsArr.push(args[name]);
    	argNamesArr.push(name);
    }
    return lx.createAndCallFunction(argNamesArr.join(','), code, null, argsArr);
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
	var arr = lx.parseFunctionString(str);
	return lx.createFunction(arr[0], arr[1]);
};

lx.parseFunctionString = function(str) {
	if (str[0] == '(') {
		var reg = /^\(\s*([\w\W]*?)\s*\)\s*=>\s*{?([\w\W]*?)}?\s*$/,
			arr = reg.exec(str);
		if (!arr.len) return null;
		return [arr[1], arr[2]];
	}

	if (str.match(/^function/)) {
		var reg = /^function[^(]*\(\s*([\w\W]*?)\s*\)\s*{\s*([\w\W]*)\s*}\s*$/,
			arr = reg.exec(str);
		if (!arr.len) return null;
		return [arr[1], arr[2]];
	}

	return [null, str];	
};

/**
 * Создать функцию по аргументам и коду
 * */
lx.createFunction = function(args, code) {
	if (code === undefined) return Function(args);
	if (args) return Function(args, code);
	return Function(code);
};
