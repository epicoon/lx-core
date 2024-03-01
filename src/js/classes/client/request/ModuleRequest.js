#lx:namespace lx;
class ModuleRequest extends lx.HttpRequest {
	constructor(moduleName, methodName, params = {}) {
		super('/lx_module', {moduleName, methodName, params});
	}
}
