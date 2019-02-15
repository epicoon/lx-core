/**
 * Сущность, ориентированная на представление в формах интерфейса, т.е. умеющая связываться с виджетами
 * */
class Model extends lx.Object #lx:namespace lx {
	constructor(data) {
		super();

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
	 * Установка внутреннего значения поля, без активации связей
	 * */
	setField(field, val) {
		this['_'+field] = val;
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
	resetField(name, withBindUpdate = true) {
		var definition = self::__schema[name];
		if (!definition) return;

		var type = definition.isObject ? definition.type : definition,
			dflt = definition.isObject ? definition.default : undefined;
		var field = withBindUpdate ? name : '_'+name;
		switch (type) {
			case 'integer':
				this[field] = [dflt, self::defaultIntegerFieldValue()].lxGetFirstDefined();
				break;
			case 'string':
				this[field] = [dflt, self::defaultStringFieldValue()].lxGetFirstDefined();
				break;
			case 'boolean':
				this[field] = [dflt, self::defaultBooleanFieldValue()].lxGetFirstDefined();
				break;
			default:
				this[field] = [dflt, self::defaultUntypedFieldValue()].lxGetFirstDefined();
		}
	}

	/**
	 * Установить схему модели
	 * schema = {
 	 * 	fieldName_0: {type:'string', size:'100', default:'some_text', notNull:true},
 	 *  fieldName_1: 'integer'
 	 * }
	 * */
	static setSchema(fields, extraFields) {
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
	 * Вернуть имена полей согласно схеме модели
	 * */
	static getFieldNames(all = false) {
		var result = [],
			schema = this.__schema;
		if (!schema) return result;
		for (var name in schema) {
			if (!all && schema[name].ref !== undefined) continue;
			result.push(name);
		}
		return result;
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
		lx.BehaviorOLD.setterListener(this, fields, extraFields);
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
	 * Вернет информацию о связи с виджетами
	 * */
	getBind() {
		return lx.Binder.getBind(this.lxBindId);
	}

	/**
	 * Вернет массив виджетов, непосредственно связанных с полем модели
	 * */
	getWidgetsForField(field) {
		var bind = this.getBind();
		if (!bind || !bind[field]) return [];
		return bind[field];
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
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		var fields = this.__fields(),
			extraFields = this.__extraFields();

		this.setSchema(fields, extraFields);
		this.makeBindable(true, extraFields);

		if (this.beforeSet) this.onBeforeSet(this.beforeSet);
		if (this.afterSet) this.onAfterSet(this.afterSet);

		super.__afterDefinition();
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
			if (data[field]) this['_'+field] = data[field];
			else this.resetField(field, false);
		}
	}
}
