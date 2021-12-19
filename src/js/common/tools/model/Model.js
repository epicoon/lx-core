class Model extends lx.Object #lx:namespace lx {
	constructor(data) {
		super();

		this.__init(data);
		this.init();
	}

	init() {
		// pass
	}

	getPk() {
		var pkName = self::__schema.getPkName();
		if (!pkName) return undefined;
		return this[pkName];
	}

	/**
	 * Инициализация полей согласно схеме
	 * */
	setFields(data) {
		if (!data || !lx.isObject(data)) return;

		var schema = self::__schema;
		if (!schema) return;

		for (let key in data)
			if (schema.hasField(key))
				this.setField(key, data[key]);
	}

	/**
	 * Возвращает указанные (или все) поля согласно схеме
	 * */
	getFields(map=null) {
		var schema = self::__schema;
		if (map === null) map = schema.getFieldNames();

		var result = {};

		map.forEach(key=>{
			if (schema.hasField(key)) result[key] = this[key];
		});

		return result;
	}

	/**
	 *
	 * */
	setField(name, value) {
		var field = self::__schema.getField(name);
		if (field.ref) {
			var code = 'this.' + field.ref + '=val;',
				f = new Function('val', code);
			f.call(this, value);
		} else {
			this[name] = value;
		}
	}

	/**
	 *
	 * */
	getField(name) {
		var field = self::__schema.getField(name);
		if (field.ref) {
			var code = 'return this.' + field.ref + ';',
				f = new Function(code);
			return f.call(this);
		}

		return this[name];
	}

	/**
	 * Сброс указанных (или всех) полей на дефолтные значения согласно схеме (или дефолтным значениям соответственно типу)
	 * */
	resetFields(map=null) {
		if (!self::__schema) return;
		if (map === null) map = self::__schema.getFieldNames();
		map.forEach(name=>this.resetField(name));
	}

	/**
	 * Сброс указанного поля на дефолтное значение согласно схеме (или дефолтным значениям соответственно типу)
	 * */
	resetField(name) {
		var definition = self::__schema.getField(name);
		if (!definition) return;

		var type = lx.isObject(definition) ? definition.type : definition,
			dflt = lx.isObject(definition) ? definition.default : undefined;

		var val;
		switch (type) {
			case 'int':
				val = lx.getFirstDefined(dflt, self::defaultIntegerFieldValue());
				break;
			case 'string':
				val = lx.getFirstDefined(dflt, self::defaultStringFieldValue());
				break;
			case 'bool':
				val = lx.getFirstDefined(dflt, self::defaultBooleanFieldValue());
				break;
			default:
				val = lx.getFirstDefined(dflt, self::defaultUntypedFieldValue());
		}
		this.setField(name, val);
	}

	/**
	 *
	 * */
	getSchema() {
		if (!self::__schema) return null;
		return self::__schema;
	}

	/**
	 *
	 * */
	static get schema() {
		if (!this.__schema) this.__schema = new lx.ModelSchema();
		return this.__schema;
	}

	/**
	 *
	 * */
	static initSchema(config) {
		this.__schema = new lx.ModelSchema(config);
	}

	/**
	 * Вернуть имена полей согласно схеме модели
	 * */
	static getFieldNames(all = false) {
		if (!this.__schema) return [];
		return this.__schema.getFieldNames(all);
	}

	/**
	 * Вернуть типы полей согласно схеме модели
	 * */
	static getFieldTypes() {
		if (!this.__schema) return [];
		return this.__schema.getFieldTypes();
	}

	/**
	 * Соответственно типу дефолтные значения для полей
	 * */
	static defaultIntegerFieldValue() { return 0; }
	static defaultStringFieldValue()  { return ''; }
	static defaultBooleanFieldValue() { return false; }
	static defaultUntypedFieldValue() { return 0; }

	/**
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		if (this.lxHasMethod('__setSchema')) this.__setSchema();
		super.__afterDefinition();
	}

	/**
	 * На основании схемы создаются поля
	 * */
	__init(data={}) {
		var schema = self::__schema;
		if (!schema) return;
		schema.eachField((field, name)=>{
			if (field.ref) return;
			if (data[name] !== undefined) this[name] = data[name];
			else this.resetField(name);
		});
	}
}
