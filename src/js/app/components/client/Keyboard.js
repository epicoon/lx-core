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
		for (let i=0; i<256; i++) __pressedKeys[i] = false;
		__pressedKey = 0;
		__pressedChar = null;
	}

	onKeydown(key, func, context = {}) {
		if (lx.isObject(key)) {
			for (let k in key) this.onKeydown(k, key[k]);
			return;
		}
		__on(__keydownHandlers, key, func, context);
	}

	offKeydown(key, func, context = {}) {
		__off(__keydownHandlers, key, func, context);
	}

	onKeyup(key, func, context = {}) {
		if (lx.isObject(key)) {
			for (let k in key) this.onKeyup(k, key[k]);
			return;
		}
		__on(__keyupHandlers, key, func, context);
	}

	offKeyup(key, func, context = {}) {
		__off(__keyupHandlers, key, func, context);
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
			let e = event || window.event,
				code = (e.charCode) ? e.charCode: e.keyCode;
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
				for (let i in __keydownHandlers['k_' + code]) {
					let pare = __keydownHandlers['k_' + code][i];
					if (!__checkContext(pare.context)) continue;
					let f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}

			if (__keydownHandlers['c_' + __pressedChar])
				for (let i in __keydownHandlers['c_' + __pressedChar]) {
					let pare = __keydownHandlers['c_' + __pressedChar][i];
					if (!__checkContext(pare.context)) continue;
					let f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}
		}

		function watchForKeyup(event) {
			let e = event || window.event,
				code = (e.charCode) ? e.charCode: e.keyCode;
			__pressedKeys[code] = false;
			__pressedCount--;

			// Отключение отслеживания клавиатуры если производится ввод текста
			if (lx.entryElement) return;

			if (__keyupHandlers['k_' + code])
				for (let i in __keyupHandlers['k_' + code]) {
					let pare = __keyupHandlers['k_' + code][i];
					if (!__checkContext(pare.context)) continue;
					let f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}

			if (__keyupHandlers['c_' + __pressedChar])
				for (let i in __keyupHandlers['c_' + __pressedChar]) {
					let pare = __keyupHandlers['c_' + __pressedChar][i];
					if (!__checkContext(pare.context)) continue;
					let f = pare.handler;
					if (lx.isFunction(f)) f(e);
					else if (lx.isArray(f)) f[1].call(f[0], e);
				}
		}

		__pressedKeys = [];
		for (let i=0; i<256; i++) __pressedKeys.push(false);
		lx.on('keydown', watchForKeydown);
		lx.on('keyup', watchForKeyup);
	}

	#lx:mode-case: dev
	status() {
		console.log('Key down handlers:');
		console.log(__keydownHandlers);
		console.log('Key up handlers:');
		console.log(__keyupHandlers);
	}
	#lx:mode-end;
}

function __on(handlers, key, func, context) {
	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
	if (!handlers[key])
		handlers[key] = [];
	handlers[key].push({handler:func, context});
}

function __off(handlers, key, func, context) {
	if (key === null && func === null) {
		for (let key in handlers) {
			let keyHandlers = handlers[key],
				tempHandlers = [];
			for (let i=0, l=keyHandlers.len; i<l; i++) {
				let keyHandler = keyHandlers[i],
					_context = keyHandler.context;
				if (context.plugin && _context.plugins) {
					_context.plugins.lxRemove(context.plugin);
					if (!_context.plugins.length) continue;
				}
				tempHandlers.push(keyHandler);
			}
			if (tempHandlers.length) handlers[key] = tempHandlers;
			else delete handlers[key];
		}
		return;
	}

	key = lx.isNumber(key) ? 'k_' + key : 'c_' + key;
	if (!handlers[key]) return;

	let index = -1;
	for (let i=0, l=handlers[key].len; i<l; i++) {
		let handler = handlers[key][i].handler;

		if ((lx.isFunction(handler) && handler === func)
			|| (lx.isArray(handler) && lx.isFunction(handler[1]) && handler[1] === func)
		) {
			index = i;
			break;
		}
	}

	if (index == -1) return;
	delete handlers[key][index].handler;
	delete handlers[key][index].context;
	handlers[key].splice(index, 1);
}

function __checkContext(context) {
	if (context.plugins) {
		for (let i in context.plugins)
			if (context.plugins[i] === lx.app.plugins.getFocusedPlugin())
				return true;
		return false;
	}

	return true;
}
