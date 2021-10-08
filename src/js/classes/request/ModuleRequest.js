class ModuleRequest extends lx.Request #lx:namespace lx {
	constructor(moduleClassName, methodName, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'module');
		this.setHeader('lx-module', moduleClassName + ':' + methodName);
	}
}
