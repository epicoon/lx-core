class WidgetRequest extends lx.Request #lx:namespace lx {
	constructor(widgetClassName, methodName, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'widget');
		this.setHeader('lx-widget', widgetClassName + ':' + methodName);
	}
}
