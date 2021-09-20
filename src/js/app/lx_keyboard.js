#lx:private

/*
lx.onKeydown(key, func)
lx.offKeydown(key, func)
lx.onKeyup(key, func)
lx.offKeyup(key, func)

lx.keyPressed(key)
lx.shiftPressed()
lx.ctrlPressed()
lx.altPressed()

lx.setWatchForKeypress(bool)
*/

// Массив для сохранения кодов зажатых символов
let pressedKeys = false,
	pressedKey = 0,
	pressedChar = null,
	pressedCount = 0,
	keydownHandlers = [],
	keyupHandlers = [];

lx.pressedCount = function() {
	return pressedCount;
};

lx.resetKeys = function() {
	for (var i=0; i<256; i++) pressedKeys[i] = false;
	pressedKey = 0;
	pressedChar = null;
};

lx.onKeydown = function(key, func) {
	if (lx.isObject(key)) {
		for (var k in key) this.onKeydown(k, key[k]);
		return;
	}

	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;

	if (!keydownHandlers[key])
		keydownHandlers[key] = [];
	keydownHandlers[key].push(func);
};

lx.offKeydown = function(key, func) {
	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
	if (!keydownHandlers[key]) return;

	var index = -1;
	for (var i=0, l=keydownHandlers[key].len; i<l; i++) {
		var handler = keydownHandlers[key][i];

		if ((lx.isFunction(handler) && handler === func) || (lx.isArray(handler) && lx.isFunction(handler[1]) && handler[1] === func)) {
			index = i;
			break;
		}
	}

	if (index == -1) return;
	keydownHandlers[key].splice(index, 1);
};

lx.onKeyup = function(key, func) {
	if (lx.isObject(key)) {
		for (var k in key) this.onKeyup(k, key[k]);
		return;
	}
	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;

	if (!keyupHandlers[key])
		keyupHandlers[key] = [];
	keyupHandlers[key].push(func);
};

lx.offKeyup = function(key, func) {
	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
	if (!keyupHandlers[key]) return;

	var index = -1;
	for (var i=0, l=keyupHandlers[key].len; i<l; i++) {
		var handler = keyupHandlers[key][i];

		if ((lx.isFunction(handler) && handler === func) || (lx.isArray(handler) && lx.isFunction(handler[1]) && handler[1] === func)) {
			index = i;
			break;
		}
	}

	if (index == -1) return;
	keyupHandlers[key].splice(index, 1);
};

lx.keyPressed = function(key) {
	if (lx.isString(key)) key = key.charCodeAt(0);
	if (pressedKeys) return pressedKeys[key]; return false;
};

lx.shiftPressed = function() { return this.keyPressed(16); };

lx.ctrlPressed = function() { return this.keyPressed(17); };

lx.altPressed = function() { return this.keyPressed(18); };

lx.setWatchForKeypress = function(bool) {
	if (!bool) {
		if (pressedKeys === false) return;
		this.off('keydown', watchForKeydown);
		this.off('keyup', watchForKeyup);
		pressedKeys = false;
		return;
	}

	function getPressedChar(event) {
		// кроссбраузерный метод получения символа
		// event.type должен быть keypress
		if (event.key) return event.key;

		if (event.which == null) { // IE
			if (event.keyCode < 32) return null; // спец. символ
				return String.fromCharCode(event.keyCode)
		}
		if (event.which != 0 && event.charCode != 0) { // все кроме IE
			if (event.which < 32) return null; // спец. символ
				return String.fromCharCode(event.which); // остальные
		}
		return null; // спец. символ
	}

	function watchForKeydown(event) {
		var e = event || window.event;
		var code = (e.charCode) ? e.charCode: e.keyCode;
		if (!pressedKeys[code]) pressedCount++;
		pressedKeys[code] = true;

		//todo - не всегда это будет удобно с 13 и 27 так поступать
		// Отключение отслеживания клавиатуры если производится ввод текста
		if ((code != 13) && (code != 27) && lx.entryElement) {
			return;
		}

		pressedKey = +code;
		pressedChar = getPressedChar(e);

		if (keydownHandlers['k_' + code])
			for (var i in keydownHandlers['k_' + code]) {
				var f = keydownHandlers['k_' + code][i];
				if (lx.isFunction(f)) f(e);
				else if (lx.isArray(f)) f[1].call(f[0], e);
			}

		if (keydownHandlers['c_' + pressedChar])
			for (var i in keydownHandlers['c_' + pressedChar]) {
				var f = keydownHandlers['c_' + pressedChar][i];
				if (lx.isFunction(f)) f(e);
				else if (lx.isArray(f)) f[1].call(f[0], e);
			}
	}

	function watchForKeyup(event) {
		var e = event || window.event;
		var code = (e.charCode) ? e.charCode: e.keyCode;
		pressedKeys[code] = false;
		pressedCount--;

		// Отключение отслеживания клавиатуры если производится ввод текста
		if (lx.entryElement) return;

		if (keyupHandlers['k_' + code])
			for (var i in keyupHandlers['k_' + code])
				keyupHandlers['k_' + code][i](e);
		if (keyupHandlers['c_' + pressedChar])
			for (var i in keyupHandlers['c_' + pressedChar])
				keyupHandlers['c_' + pressedChar][i](e);
	}

	pressedKeys = [];
	for (var i=0; i<256; i++) pressedKeys.push(false);
	this.on('keydown', watchForKeydown);
	this.on('keyup', watchForKeyup);
};
