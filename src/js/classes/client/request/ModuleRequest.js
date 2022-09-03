#lx:namespace lx;
class ModuleRequest extends lx.HttpRequest {
	constructor(moduleClassName, methodName, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'module');
		this.setHeader('lx-module', moduleClassName + ':' + methodName);
	}
}
