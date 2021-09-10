class ServiceRequest extends lx.Request #lx:namespace lx {
	constructor(meta, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'service');
		this.setHeader('lx-service', meta);
	}
}
