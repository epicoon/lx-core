class BindableModel extends lx.Model #lx:namespace lx {
	/**
	 * Связать модель с виджетами
	 * */
	bind(widgets, type=lx.Binder.BIND_TYPE_FULL) {
		if (!lx.isArray(widgets)) widgets = [widgets];
		widgets.forEach((widget)=>lx.Binder.bind(this, widget, type));
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
	bindRefresh(fieldNames = null) {
		lx.Binder.refresh(this, fieldNames);
	}

	/**
	 * Отвязать от модели все привязанные виджеты по всем полям (или переданный виджет)
	 * */
	unbind(widget = null) {
		lx.Binder.unbind(this, widget);
	}

	/**
	 *
	 * */
	static setterListenerFields() {
		return this.schema.getFieldsExportDefinition();
	}

	/**
	 *
	 * */
	static dropSchema() {
		if (!this.__schema || this.__schema.isEmpty) return;

		var fieldNames = this.getFieldNames(true);
		fieldNames.forEach((name)=>{
			delete (this.prototype[name]);
		});
		this.__schema.fields = {};
	}

	/**
	 *
	 * */
	static initSchema(config) {
		this.dropSchema();
		super.initSchema(config);
	}

	/**
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		super.__afterDefinition();
		lx.SetterListenerBehavior.injectInto(this);
		this.afterSet(function(field){lx.Binder.refresh(this, field)});
	}

	__init(data={}) {
		this.ignoreSetterListener(true);
		super.__init(data);
		this.ignoreSetterListener(false);
	}
}
