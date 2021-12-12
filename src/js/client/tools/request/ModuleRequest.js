class ModuleRequest extends lx.HttpRequest #lx:namespace lx {
	constructor(moduleClassName, methodName, params = {}) {
		super('', params);
		this.setHeader('lx-type', 'module');
		this.setHeader('lx-module', moduleClassName + ':' + methodName);
	}
}
