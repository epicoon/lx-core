let __pressedKeys = false,
	__pressedKey = 0,
	__pressedChar = null,
	__pressedCount = 0,
	__keydownHandlers = [],
	__keyupHandlers = [];

#lx:namespace lx;
class Keyboard extends lx.AppComponent {
	pressedCount() {
		return __pressedCount;
	}

	resetKeys() {
		for (var i=0; i<256; i++) __pressedKeys[i] = false;
		__pressedKey = 0;
		__pressedChar = null;
	}

	onKeydown(key, func, context = {}) {
		if (lx.isObject(key)) {
			for (var k in key) this.onKeydown(k, key[k]);
			return;
		}

		key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;

		if (!__keydownHandlers[key])
			__keydownHandlers[key] = [];
		__keydownHandlers[key].push({handler:func, context});
	}

	offKeydown(key, func, context = {}) {
		if (key === null && func === null) {
			var temp = {};
			for (var key in __keydownHandlers) {
				var keysTemp = [];
				for (var i=0, l=__keydownHandlers[key].len; i<l; i++) {
					var _context = __keydownHandlers[key][i].context;
					if (context.plugin && context.plugin === _context.plugin) continue;
					keysTemp.push(__keydownHandlers[key][i]);
				}
				temp[key]= keysTemp;
			}
			__keydownHandlers = temp;
			return;
		}

		key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
		if (!__keydownHandlers[key]) return;

		var index = -1;
		for (var i=0, l=__keydownHandlers[key].len; i<l; i++) {
			var handler = __keydownHandlers[key][i].handler;

			if ((lx.isFunction(handler) && handler === func) || (lx.isArray(handler) && lx.isFunction(handler[1]) && handler[1] === func)) {
				index = i;
				break;
			}
		}

		if (index == -1) return;
		__keydownHandlers[key].splice(index, 1);
	}

	onKeyup(key, func, context = {}) {
		if (lx.isObject(key)) {
			for (var k in key) this.onKeyup(k, key[k]);
			return;
		}
		key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;

		if (!__keyupHandlers[key])
			__keyupHandlers[key] = [];
		__keyupHandlers[key].push({handler:func, context});
	}

	offKeyup(key, func, context = {}) {
		if (key === null && func === null) {
			var temp = {};
			for (var key in __keyupHandlers) {
				var keysTemp = [];
				for (var i=0, l=__keyupHandlers[key].len; i<l; i++) {
					var _context = __keyupHandlers[key][i].context;
					if (context.plugin && context.plugin === _context.plugin) continue;
					keysTemp.push(__keyupHandlers[key][i]);
				}
				temp[key]= keysTemp;
			}
			__keyupHandlers = temp;
			return;
		}

		key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
		if (!__keyupHandlers[key]) return;

		var index = -1;
		for (var i=0, l=__keyupHandlers[key].len; i<l; i++) {
			var handler = __keyupHandlers[key][i].handler;

			if ((lx.isFunction(handler) && handler === func) || (lx.isArray(handler) && lx.isFunction(handler[1]) && handler[1] === func)) {
				index = i;
				break;
			}
		}

		if (index == -1) return;
		__keyupHandlers[key].splice(index, 1);
	}

	keyPressed(key) {
		if (lx.isString(key)) key = key.charCodeAt(0);
		if (__pressedKeys) return __pressedKeys[key];
		return false;
	}

	shiftPressed() { return this.keyPressed(16); }

	ctrlPressed() { return this.keyPressed(17); }

	altPressed() { return this.keyPressed(18); }

	setWatchForKeypress(bool) {
		if (!bool) {
			if (__pressedKeys === false) return;
			lx.off('keydown', watchForKeydown);
			lx.off('keyup', watchForKeyup);
			__pressedKeys = false;
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
			if (!__pressedKeys[code]) __pressedCount++;
			__pressedKeys[code] = true;

			//todo - не всегда это будет удобно с 13 и 27 так поступать
			// Отключение отслеживания клавиатуры если производится ввод текста
			if ((code != 13) && (code != 27) && lx.entryElement) {
				return;
			}

			__pressedKey = +code;
			__pressedChar = getPressedChar(e);

			if (__keydownHandlers['k_' + code])
				for (var i in __keydownHandlers['k_' + code]) {
					var pare = __keydownHandlers['k_' + code][i];
					if (!__checkContext(pare.context)) continue;
					var f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}

			if (__keydownHandlers['c_' + __pressedChar])
				for (var i in __keydownHandlers['c_' + __pressedChar]) {
					var pare = __keydownHandlers['c_' + __pressedChar][i];
					if (!__checkContext(pare.context)) continue;
					var f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}
		}

		function watchForKeyup(event) {
			var e = event || window.event;
			var code = (e.charCode) ? e.charCode: e.keyCode;
			__pressedKeys[code] = false;
			__pressedCount--;

			// Отключение отслеживания клавиатуры если производится ввод текста
			if (lx.entryElement) return;

			if (__keyupHandlers['k_' + code])
				for (var i in __keyupHandlers['k_' + code]) {
					var pare = __keyupHandlers['k_' + code][i];
					if (!__checkContext(pare.context)) continue;
					var f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}

			if (__keyupHandlers['c_' + __pressedChar])
				for (var i in __keyupHandlers['c_' + __pressedChar]) {
					var pare = __keyupHandlers['c_' + __pressedChar][i];
					if (!__checkContext(pare.context)) continue;
					var f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}
		}

		__pressedKeys = [];
		for (var i=0; i<256; i++) __pressedKeys.push(false);
		lx.on('keydown', watchForKeydown);
		lx.on('keyup', watchForKeyup);
	}
}

function __checkContext(context) {
	var check = true;
	if (context.plugin !== undefined && context.plugin !== lx.app.plugins.getFocusedPlugin())
		check = false;

	return check;
}
