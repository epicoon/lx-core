class BackupedModelBehavior extends lx.Behavior #lx:namespace lx {
	/**
	 *
	 * */
	static injectInto(supportedClass, config=null) {
		super.injectInto(supportedClass);

		class Backup extends lx.Model {};
		var schema = {};
		var keys = supportedClass.backupedFields();
		for (var i=0,l=keys.len; i<l; i++) {
			var key = keys[i];
			schema[key] = supportedClass.schema.getField(key);
		}
		Backup.initSchema(schema);
		supportedClass.backupClass = Backup;
	}

	/**
	 *
	 * */
	static overridedMethods() {
		return ['initSchema'];
	}

	/**
	 *
	 * */
	static initSchema(config) {

		// console.log(config);
		// console.dir(this);
		// console.log(lx.BindableModel.initSchema);
		// console.log( super.initSchema );
		// console.log(this.__proto__.initSchema);

		this.__proto__.initSchema.call(this, config);

		// super.initSchema(config);

		var schema = {};
		var keys = this.backupedFields();
		for (var i=0,l=keys.len; i<l; i++) {
			var key = keys[i];
			schema[key] = this.schema.getField(key);
		}
		this.backupClass.initSchema(schema);
	}

	/**
	 *
	 * */
	onAfterConstruct(supportedObject) {
		supportedObject.backup = new supportedObject.constructor.backupClass();
		supportedObject.commit();
	}

	/**
	 *
	 * */
	static backupedFields() {
		return this.getFieldNames();
	}

	/**
	 *
	 * */
	commit() {
		this.backup.getSchema().getFieldNames().forEach(key=>{
			var val = this[key];
			if (val && (lx.isArray(val) || lx.isObject(val))) val = val.lxClone();
			this.backup[key] = val;
		});
		this.onCommit();
	}

	/**
	 *
	 * */
	reset() {
		this.backup.getSchema().getFieldNames().forEach(key=>{
			var val = this.backup.getField(key);
			if (val && (lx.isArray(val) || lx.isObject(val))) val = val.lxClone();
			this[key] = val;
		});
		this.onReset();
	}

	/**
	 *
	 * */
	differences() {
		var result = [],
			fields = this.backup.getSchema().getFieldNames();
		for (var i=0, l=fields.len; i<l; i++)
			if (this[fields[i]] != this.backup[fields[i]])
				result.push(fields[i]);
		return result;
	}

	/**
	 *
	 * */
	changed() {
		return differences.len > 0;
	}

	/**
	 *
	 * */
	onCommit() {}

	/**
	 *
	 * */
	onReset() {}
}
