#lx:module lx.Form;

#lx:use lx.Box;
#lx:use lx.Button;

/**
 * @widget lx.Form
 */
#lx:namespace lx;
class Form extends lx.Box {
	/**
	 * list - хэш-таблица, записи - {name, info} - преобразуются в аргументы для метода .field(className, fieldName, config)
	 *	name - имя, которое станет значением ключа нового элемента и значением поля field
	 *	info - либо класс для создания виджета, либо массив из класса и конфига
	 * */
	fields(list) {
		for (var key in list) {
			var item = list[key],
				config = {},
				widget;
			if (lx.isArray(item)) {
				widget = item[0];
				config = item[1];
			} else widget = item;
			this.field(key, widget, config);
		}
	}

	/**
	 * className - класс виджета
	 * fieldName - имя, которое станет значением ключа нового элемента и значением поля field
	 * config - конфиг для создания виджета
	 * */
	field(fieldName, className, config = {}) {
		if (config.after && config.after.parent !== this) delete config.after;
		if (config.before && config.before.parent !== this) delete config.before;
		config.parent = this;

		config.key = fieldName;
		config.field = fieldName;
		var elem = new className(config);
	}

	/**
	 * Содержимым считаются значения элементов, у которых есть поле field
	 * */
	content(map=null) {
		let obj = {};
		var list = this.getChildren({ hasProperty: '_field', all: true });
		list.forEach(a=>{
			if (map !== null && lx.isArray(map) && !map.includes(a._field)) return;
			obj[a._field] = a.lxHasMethod('value')
				? a.value()
				: a.text();
		});
		return obj;
	}

	getFields(types = null) {
		if (types === null)
			return this.getChildren({ hasProperty: '_field', all: true });

		types = lx.isArray(types) ? types : [types];
		return this.getChildren(child=>{
			if (!('_field' in child)) return false;
			let match = false;
			types.forEach(type => {
				if (lx.isInstance(child, type)) match = true;
			});
			return match;
		}, true);
	}

	/**
	 * Добавление кнопки полезная и частая операция в форме - этот метод для более короткого кода создания кнопки
	 * */
	addButton(text='', config={}, onClick=null) {
		if (lx.isFunction(config)) {
			config = {
				click: config
			};
		} else if (lx.isFunction(onClick)) {
			config.click = onClick;
		}
		config.text = text;
		return this.add(lx.Button, config);
	}








	//TODO - в реальноости не использовал такое. Выглядит интересно, если до ума довести, может есть смысл
	// в текущем виде точно не годится, даже виджета lx.LabeledBox уже не существует
	// /**
	//  * list - хэш-таблица, записи - {name: info} - аргументы для метода .labeledField(name, info)
	//  * */
	// labeledFields(list) {
	// 	for (var key in list) this.labeledField(key, list[key]);
	// }
	// /**
	//  * name - имя, которое станет значением ключа нового элемента и значением поля field
	//  * info - массив в форматах:
	//  * 	1. [label, className, config] - подпись виджета будет слева
	//  *	2. [className, label, config] - подпись виджета будет справа
	//  *		- congif не обязателен
	//  * */
	// labeledField(name, info) {
	// 	var fieldConfig;
	// 	if (lx.isArray(info)) {
	// 		fieldConfig = info[2] || {};
	// 		if (lx.isString(info[0])) {
	// 			fieldConfig.label = info[0];
	// 			fieldConfig.widget = info[1];
	// 		} else {
	// 			if (fieldConfig.labelOrientation === undefined) fieldConfig.labelOrientation = lx.RIGHT;
	// 			fieldConfig.widget = info[0];
	// 			fieldConfig.label = info[1];
	// 		}
	// 	}
	// 	this.field(name, lx.LabeledBox, fieldConfig);
	// }
}
