class ServiceRequest extends lx.HttpRequest #lx:namespace lx {
	constructor(meta, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'service');
		this.setHeader('lx-service', meta);
	}
}
