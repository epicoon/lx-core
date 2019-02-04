/**
 * Сущность, ориентированная на представление в формах интерфейса, т.е. умеющая связываться с виджетами
 * */
class Model #in lx {
	constructor(data) {
		this.__init(data);
	}

	/**
	 * Соответственно типу дефолтные знаяения для полей
	 * */
	static defaultIntegerFieldValue() { return 0; }
	static defaultStringFieldValue()  { return ''; }
	static defaultBooleanFieldValue() { return false; }
	static defaultUntypedFieldValue() { return 0; }

	/**
	 * Инициализация полей согласно схеме
	 * */
	setFields(data) {
		if (!data || !data.isObject) return;

		var schema = self::__schema;

		for (let key in data)
			if (key in schema)
				this[key] = data[key];
	}

	/**
	 * Возвращает указанные (или все) поля согласно схеме
	 * */
	getFields(map=null) {
		var schema = self::__schema;
		if (map === null) map = schema.lxGetKeys();

		var result = {};

		map.each((key)=> {
			if (key in schema) result[key] = this[key];
		});

		return result;
	}

	/**
	 * Сброс указанных (или всех) полей на дефолтные значения согласно схеме (или дефолтным значениям соответственно типу)
	 * */
	resetFields(map=null) {
		if (map === null) map = self::__schema.lxGetKeys();
		map.each((key)=> this.resetField(key));
	}

	/**
	 * Сброс указанного поля на дефолтное значение согласно схеме (или дефолтным значениям соответственно типу)
	 * */
	resetField(name) {
		var definition = self::__schema[name];
		if (!definition) return;

		var type = definition.isObject ? definition.type : definition,
			dflt = definition.isObject ? definition.default : undefined;
		switch (type) {
			case 'integer':
				this[name] = [dflt, self::defaultIntegerFieldValue()].getFirstDefined();
				break;
			case 'string':
				this[name] = [dflt, self::defaultStringFieldValue()].getFirstDefined();
				break;
			case 'boolean':
				this[name] = [dflt, self::defaultBooleanFieldValue()].getFirstDefined();
				break;
			default:
				this[name] = [dflt, self::defaultUntypedFieldValue()].getFirstDefined();
		}
	}

	/**
	 * Вернуть типы полей согласно схеме модели
	 * */
	static getFieldTypes() {
		var result = {},
			schema = this.__schema;
		if (!schema) return result;
		for (var name in schema) {
			var type = schema[name].isObject 
				? schema[name].type
				: schema[name];

			if (type == 'integer' && schema[name].isObject && schema[name].default == '@PK') type = 'pk';
			result[name] = type;
		}
		return result;
	}

	/**
	 * Добавить возможность отслеживать изменение полей экземпляров класса
	 * */
	static genSetters(fields, extraFields) {
		if (fields.isObject) {
			extraFields = fields;
			fields = true;
		}
		lx.Behavior.setterListener(this, fields, extraFields);
	}

	/**
	 * Сделать класс привязываемым к виджетам
	 * */
	static makeBindable(fields, extraFields) {
		if (fields && fields.isObject) {
			extraFields = fields;
			fields = true;
		}
		lx.Binder.makeBindable(this, fields, extraFields);
	}

	/**
	 * Связать модель с виджетами
	 * */
	bind(...widgets) {
		widgets.each((widget)=> lx.Binder.bind(this, widget));
	}

	/**
	 * Активно просигнализировать виджетам об изменении состояния
	 * */
	bindRenew() {
		lx.Binder.renew(this);
	}

	/**
	 * Отвязать от модели все привязанные виджеты по всем полям
	 * */
	unbind() {
		lx.Binder.unbind(this);
	}

	//=========================================================================================================================
	// Методы для внутренней работы

	/**
	 * Метод автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		var fields = this.__fields(),
			extraFields = this.__extraFields();

		this.__setSchema(fields, extraFields);
		this.makeBindable(true, extraFields);
	}

	/**
	 * schema = {
 	 * 	fieldName_0: {type:'string', len:'100', default:'some_text', is_nullable:true},
 	 *  fieldName_1: 'integer'
 	 * }
	 * */
	static __setSchema(fields, extraFields) {
		var schema = {};
		for (let key in fields) {
			if (key.isNumber) schema[fields[key]] = {};
			else schema[key] = fields[key];
		}
		for (let key in extraFields) {
			let value = extraFields[key];
			if (value.isString) schema[key] = {ref:value};
			else schema[key] = value;
		}
		this.__schema = schema;
	}

	/**
	 * Методы для описания полей в определении класса
	 * */
	 // Для переопределения в клиентском коде
	static fields()       { return {}; }
	static extraFields()  { return {}; }
	// Генерируются js-компилятором из расширенного синтаксиса
	static _fields()      { return {}; }
	static _extraFields() { return {}; }
	// Собирают все поля
	static __fields() {
		let fields = this.fields().lxMerge(this._fields());
		if (this.__proto__.__fields)
			fields = fields.lxMerge(this.__proto__.__fields());

		return fields;
	}
	static __extraFields() {
		let fields = this.extraFields().lxMerge(this._extraFields());
		if (this.__proto__.__extraFields)
			fields = fields.lxMerge(this.__proto__.__extraFields());

		return fields;
	}

	/**
	 * На основании схемы создаются поля, которые являются привязываемыми
	 * */
	__init(data={}) {
		var schema = self::__schema;
		if (!schema) return;

		for (var field in schema) {
			if (schema[field].ref) continue;
			if (data[field]) this[field] = data[field];
			else this.resetField(field);
		}
	}
}
