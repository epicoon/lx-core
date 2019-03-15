class BindableModel extends lx.Model #lx:namespace lx {
	constructor(data) {
		super(false);
		if (!self::__schema) return;
		this.ignoreSetterListener(true);
		this.__init(data);
		this.ignoreSetterListener(false);
	}

	/**
	 * Связать модель с виджетами
	 * */
	bind(widgets, type=lx.Binder.BIND_TYPE_FULL) {
		if (!widgets.isArray) widgets = [widgets];
		widgets.each((widget)=>lx.Binder.bind(this, widget, type));
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
	bindRefresh() {
		lx.Binder.refresh(this);
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
		fieldNames.each((name)=>{
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
		lx.SetterListenerBehavior.inject(this);
	}

	/**
	 * Метод, автоматически вызываемый после определения класса
	 * */
	static __afterDefinition() {
		super.__afterDefinition();
		if (this.lxHasMethod('afterSet')) this.afterSet(function(field){lx.Binder.refresh(this, field)});
	}
}
