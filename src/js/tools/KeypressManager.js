/**
 * Менеджер для инициализации хоткеев
 * Нужно отнаследовать свой менеджер от этого класса и объявлять в нем методы с префиксом 'on_' - это обработчики нажатия клавиш
 * Имя методов обработчиков завершается кодом, либо символом нажатой клавиши, например:
 *  - 'on_13' - нажатие на enter
 *  - 'on_a' - нажатие на клавишу с символом 'a'
 * Другой способ создать обработчик - использовать префикс 'key_', тогда имя метода может заканчиваться произвольно (в рамках правил именования метода),
 * а для этого произвольного имени должно быть соответствие, указанное в методе KeypressManager::keys() - символ, код, либо массив символов и кодов, например:
 *	- 'key_test' - имя метода, ключ 'test'
 *	- keys() { return {test: [13, 'a']}; } - метод будет срабатывать при нажатии 'enter' или клавиши 'a'
 * */
class KeypressManager extends lx.Singleton #lx:namespace lx {
	init() {
		var funcs = this.lxGetAllProperties(),
			keys = this.keys(),
			handlers = {},
			multiHandlers = {};
		for (var i=0, l=funcs.len; i<l; i++) {
			var funcName = funcs[i];
			if (funcName.match(/^on_/)) this.__handleOn(funcName, handlers);
			else
				if (funcName.match(/^key_/)) this.__handleKey(funcName, keys, handlers, multiHandlers);
		}
		for (let key in handlers)
			for (let i=0, l=handlers[key].length; i<l; i++)
				lx.onKeydown(key, handlers[key][i]);
	}

	keys() {
		return {};
	}

	__handleOn(funcName, handlers) {
		var key = funcName.split('on_')[1];
		if (!(key in handlers)) handlers[key] = [];
		handlers[key].push([this, this[funcName]]);
	}

	__handleKey(funcName, keys, handlers, multiHandlers) {
		var key = funcName.split('key_')[1],
			hotkeys = keys[key];

		if (!hotkeys) return;
		if (!hotkeys.isArray) hotkeys = [hotkeys];
		hotkeys.each((a)=> {
			if (a.match(/\+/)) {
				let arr = a.split('+');
				arr.each((item, i)=> {
					if (item != '' && !item.isNumber) arr[i] = item.toUpperCase().charCodeAt(0);
				});
				let main = arr.pop(),
					f = function(e) {
						if (arr.len == 1 && arr[0] == '') {
							if (lx.pressedCount() == 1) this[funcName]();
							return;
						}

						for (let i=0, l=arr.len; i<l; i++)
							if (!lx.keyPressed(arr[i])) return;
						this[funcName](e);
					};
				if (!(main in handlers)) handlers[main] = [];
				handlers[main].push([this, f]);
				return;
			}

			if (!(a in handlers)) handlers[a] = [];
			handlers[a].push([this, this[funcName]]);
		});
	}
}
