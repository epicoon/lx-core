class BackupedModelBehavior extends lx.Behavior #lx:namespace lx {
	/**
	 *
	 * */
	static inject(supportedClass, config=null) {
		super.inject(supportedClass);

		class Backup extends lx.Model {
			getField(name) {return this['_'+name];}
			static __afterDefinition(){}
		};
		var schema = {};
		var keys = supportedClass.backupedFields();
		if (keys === null) keys = supportedClass.__schema.lxGetKeys();
		for (var i=0,l=keys.len; i<l; i++) {
			var key = keys[i];
			if (supportedClass.__schema[key].ref === undefined)
				schema[key] = supportedClass.__schema[key];
		}
		Backup.setSchema(schema);
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
		for (var key in this.backup.constructor.__schema) {
			var val = this[key];
			if (val && (val.isArray || val.isObject)) val = val.lxCopy();
			this.backup['_'+key] = val;
		}
		this.onCommit();
	}

	/**
	 *
	 * */
	reset() {
		for (var key in this.backup.constructor.__schema) {
			var val = this.backup.getField(key);
			if (val && (val.isArray || val.isObject)) val = val.lxCopy();
			this[key] = val;
		}
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
