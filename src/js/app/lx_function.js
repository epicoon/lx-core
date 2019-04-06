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
	else if (data.isArray) data[0].apply(data[1], args);
};

/**
 * По переданным строкам аргументов и кода создает функцию и сразу ее запускает
 * */
lx.createAndCallFunction = function(args, code, context=null, params=[]) {
	var f = lx.createFunction(args, code);
	f.apply(context, params);
};

/**
 * Создает функцию из синтаксиса '(arg1, arg2) => ...function code'
 * */
lx.createFunctionByInlineString = function(str) {
	var reg = /^\(([\w\W]*?)\)\s*=>\s*([\w\W]*)/g,
		arr = reg.exec(str);
	if (!arr.len) return null;
	return lx.createFunction(arr[1], arr[2]);
};

/**
 * Создать функцию по аргументам и коду
 * */
lx.createFunction = function(args, code) {
	// code = lx.functionPrefix(code);  // Раньше был префикс для всех функций, теперь его нет, но вдруг что-то похожее снова возникнет, пока такая обертка
	return Function(args, code);
};
