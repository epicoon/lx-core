/**
 * Менеджер для инициализации хоткеев
 * Нужно отнаследовать свой менеджер от этого класса и объявлять в нем методы с префиксом 'on_' - это обработчики нажатия клавиш
 * Имя методов обработчиков завершается кодом, либо символом нажатой клавиши, например:
 *  - 'on_13' - нажатие на enter
 *  - 'on_a' - нажатие на клавишу с символом 'a'
 * Другой способ создать обработчик - использовать префикс 'key_', тогда имя метода может заканчиваться произвольно (в рамках правил именования методов),
 * а для этого произвольного имени должно быть соответствие, указанное в методе KeypressManager::keys() - символ, код, либо массив символов и кодов, например:
 *	- 'key_test' - имя метода, ключ 'test'
 *	- keys() { return {test: [13, 'a']}; } - метод будет срабатывать при нажатии 'enter' или клавиши 'a'
 */
#lx:namespace lx;
class KeypressManager {
	#lx:const
		ENTER = 13;

	constructor() {
		this.context = {};
		this.init();
	}

	init() {}

	setContext(context) {
		this.context = context;
	}

	run() {
		var funcs = this.lxGetAllProperties(),
			upHandlers = {},
			downHandlers = {};
		for (var i=0, l=funcs.len; i<l; i++) {
			var funcName = funcs[i];
			switch (true) {
				case !!funcName.match(/^onUp_/):
					__handleOn(this, funcName, upHandlers);
					break;
				case !!funcName.match(/^onDown_/):
				case !!funcName.match(/^on_/):
					__handleOn(this, funcName, downHandlers);
					break;
				case !!funcName.match(/^keyUp_/):
					__handleKey(this, funcName, upHandlers);
					break;
				case !!funcName.match(/^keyDown_/):
				case !!funcName.match(/^key_/):
					__handleKey(this, funcName, downHandlers);
					break;
			}
		}

		for (let key in upHandlers)
			for (let i=0, l=upHandlers[key].length; i<l; i++)
				lx.app.keyboard.onKeyup(key, upHandlers[key][i], this.context);
		for (let key in downHandlers)
			for (let i=0, l=downHandlers[key].length; i<l; i++)
				lx.app.keyboard.onKeydown(key, downHandlers[key][i], this.context);
	}

	keys() {
		return {};
	}
}

function __handleOn(self, funcName, handlers) {
	var key = funcName.replace(/on(Up|Down)?_/, '');
	__pushHandler(self, key, self[funcName], handlers);
}

function __handleKey(self, funcName, handlers) {
	var key = funcName.replace(/key(Up|Down)?_/, ''),
		hotkeys = self.keys()[key];

	if (!hotkeys) return;
	if (!lx.isArray(hotkeys)) hotkeys = [hotkeys];

	hotkeys.forEach(a=>{
		if (lx.isString(a) && a.match(/\+/)) {
			let arr = a.split('+');
			arr.forEach((item, i)=> {
				if (item != '' && !lx.isNumber(item)) arr[i] = item.toUpperCase().charCodeAt(0);
			});
			let main = arr.pop(),
				f = function(e) {
					if (arr.len == 1 && arr[0] == '') {
						if (lx.app.keyboard.pressedCount() == 1) self[funcName]();
						return;
					}

					for (let i=0, l=arr.len; i<l; i++)
						if (!lx.app.keyboard.keyPressed(arr[i])) return;
					self[funcName](e);
				};
			__pushHandler(self, main, f, handlers);
			return;
		}

		__pushHandler(self, a, self[funcName], handlers);
	});
}

function __pushHandler(self, key, handler, handlers) {
	if (!(key in handlers)) handlers[key] = [];
	handlers[key].push([self, handler]);
}
