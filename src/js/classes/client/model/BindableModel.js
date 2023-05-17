#lx:namespace lx;
class BindableModel extends lx.Model {
	constructor(data) {
		super(data);
		this.__onValidateFailed = null;
	}

	static onValidateFailed(field, value) {
		// pass
	}

	onValidateFailed(callback) {
		this.__onValidateFailed = callback;
	}

	/**
	 * Связать модель с виджетами
	 */
	bind(widgets, type=lx.app.binder.BIND_TYPE_FULL) {
		if (!lx.isArray(widgets)) widgets = [widgets];
		widgets.forEach((widget)=>lx.app.binder.bind(this, widget, type));
	}

	/**
	 * Вернет информацию о связи с виджетами
	 */
	getBind() {
		return lx.app.binder.getBind(this.lxBindId);
	}

	/**
	 * Вернет массив виджетов, непосредственно связанных с полем модели
	 */
	getWidgetsForField(field) {
		var bind = this.getBind();
		if (!bind || !bind[field]) return [];
		return bind[field];
	}

	/**
	 * Активно просигнализировать виджетам об изменении состояния
	 */
	bindRefresh(fieldNames = null) {
		lx.app.binder.refresh(this, fieldNames);
	}

	/**
	 * Отвязать от модели все привязанные виджеты по всем полям (или переданный виджет)
	 */
	unbind(widget = null) {
		lx.app.binder.unbind(this, widget);
	}

	static setterListenerFields() {
		return this.schema.getFieldsExportDefinition();
	}

	static dropSchema() {
		if (!this.__schema || this.__schema.isEmpty) return;

		var fieldNames = this.getFieldNames(true);
		fieldNames.forEach((name)=>{
			delete (this.prototype[name]);
		});
		this.__schema.fields = {};
	}

	static initSchema(config) {
		this.dropSchema();
		super.initSchema(config);
	}

	/**
	 * Метод, автоматически вызываемый после определения класса
	 */
	static __afterDefinition() {
		super.__afterDefinition();
		lx.SetterListenerBehavior.injectInto(this);
		this.beforeSet(function(field, value) {
			const def = this.getSchema().getField(field);
			if (!def.type) return;
			let result = true;
			switch (def.type) {
				case lx.ModelTypeEnum.INTEGER:
					if (!lx.isNumber(value))
						result = false;
					else return +value;
					break;
				case lx.ModelTypeEnum.STRING:
					if (!lx.isString(value))
						result = false;
					break;
				case lx.ModelTypeEnum.BOOLEAN:
					if (!lx.isBoolean(value))
						result = false;
					break;
			}
			if (result === false) {
				self::onValidateFailed(field, value);
				if (this.__onValidateFailed)
					this.__onValidateFailed.call(this, field, value);
				return this[field];
			}
		});
		this.afterSet(function(field) {
			lx.app.binder.refresh(this, field)
		});
	}

	__init(data={}) {
		this.ignoreSetterListener(true);
		super.__init(data);
		this.ignoreSetterListener(false);
	}
}
