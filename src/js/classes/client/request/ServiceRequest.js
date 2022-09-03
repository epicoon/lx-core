#lx:namespace lx;
class ServiceRequest extends lx.HttpRequest {
	constructor(meta, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'service');
		this.setHeader('lx-service', meta);
	}
}
