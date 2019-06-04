#lx:use lx.Box as Box;
#lx:use lx.Button as Button;

class Form extends Box #lx:namespace lx {
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
			if (item.isArray) {
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
	 * list - хэш-таблица, записи - {name: info} - аргументы для метода .labeledField(name, info)
	 * */
	labeledFields(list) {
		for (var key in list) this.labeledField(key, list[key]);
	}

	/**
	 * name - имя, которое станет значением ключа нового элемента и значением поля field
	 * info - массив в форматах:
	 * 	1. [label, className, config] - подпись виджета будет слева
	 *	2. [className, label, config] - подпись виджета будет справа
	 *		- congif не обязателен
	 * */
	labeledField(name, info) {
		var fieldConfig;
		if (info.isArray) {
			fieldConfig = info[2] || {};
			if (info[0].isString) {
				fieldConfig.label = info[0];
				fieldConfig.widget = info[1];
			} else {
				if (fieldConfig.labelOrientation === undefined) fieldConfig.labelOrientation = lx.RIGHT;
				fieldConfig.widget = info[0];
				fieldConfig.label = info[1];
			}
		}
		this.field(name, lx.LabeledBox, fieldConfig);
	}

	/**
	 * Содержимым считаются значения элементов, у которых есть поле field
	 * */
	content(map=null) {
		let obj = {};
		var children = this.getChildren({ hasProperties: '_field', all: true });
		children.each((a)=>{
			if (map !== null && map.isArray && !map.contain(a._field)) return;
			obj[a._field] = a.lxHasMethod('value')
				? a.value()
				: a.text();
		});
		return obj;
	}

	/**
	 * Добавление кнопки полезная и частая операция в форме - для более короткого кода создания кнопки есть метод
	 * */
	button(text='', config={}, onClick=null) {
		if (config.isFunction) {
			config = {
				click: config
			};
		} else if (onClick && onClick.isFunction) {
			config.click = onClick;
		}
		config.text = text;
		return this.add(Button, config);
	}
}
