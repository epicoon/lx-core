class ModelSchema #lx:namespace lx {
	constructor(config = null) {
		this.fields = {};
		if (config) this.set(config);
	}

	get isEmpty() {
		return this.fields.lxEmpty;
	}

	/**
	 *
	 * */
	set(list) {
		this.fields = list.lxCopy();
	}

	/**
	 *
	 * */
	getField(name) {
		return this.fields[name];
	}

	/**
	 *
	 * */
	hasField(name) {
		return name in this.fields;
	}

	/**
	 * @param all - возвращать с полями, имеющими ref, или без них
	 * */
	getFieldNames(all = false) {
		var result = [];
		for (var name in this.fields) {
			if (!all && this.fields[name].ref !== undefined) continue;
			result.push(name);
		}
		return result;
	}

	/**
	 * Вернуть типы полей
	 * */
	getFieldTypes() {
		var result = {};
		for (var name in this.fields) {
			var type = this.fields[name].isObject 
				? this.fields[name].type
				: this.fields[name];
			result[name] = type;
		}
		return result;
	}

	/**
	 *
	 * */
	eachField(func) {
		for (var name in this.fields)
			func(this.fields[name], name);
	}

	/**
	 *
	 * */
	getFieldsExportDefinition() {
		var result = [];
		for (var name in this.fields) {
			var def = this.fields[name];
			if (def.ref) result.push(name + ' << ' + def.ref);
			else result.push(name);
		}

		return result;
	}
}
