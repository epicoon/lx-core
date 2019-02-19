class BackupedModelBehavior extends lx.Behavior #lx:namespace lx {
	/**
	 *
	 * */
	static inject(supportedClass, config=null) {
		super.inject(supportedClass);

		class Backup extends lx.Model {};
		var schema = {};
		var keys = supportedClass.backupedFields();
		if (keys === null) keys = supportedClass.schema.getFieldNames();
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
	onAfterConstruct(supportedObject) {
		supportedObject.backup = new supportedObject.constructor.backupClass();
		supportedObject.commit();
	}

	/**
	 *
	 * */
	static backupedFields() {
		return null;
	}

	/**
	 *
	 * */
	commit() {
		this.backup.getSchema().getFieldNames().each((key)=>{
			var val = this[key];
			if (val && (val.isArray || val.isObject)) val = val.lxCopy();
			this.backup[key] = val;
		});
		this.onCommit();
	}

	/**
	 *
	 * */
	reset() {
		this.backup.getSchema().getFieldNames().each((key)=>{
			var val = this.backup.getField(key);
			if (val && (val.isArray || val.isObject)) val = val.lxCopy();
			this[key] = val;
		});
		this.onReset();
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
