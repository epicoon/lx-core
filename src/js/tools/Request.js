class Request #in lx {
	constructor(url = '', params = {}) {
		this.method = 'post';
		this.url = url;
		this.isAjax = true;

		this.headers = {};
		this.params = params;

		//todo Спорное решение. В рамках платформы может и логично.
		this.module = null;
	}

	get successMethodName() { return 'success' }
	get errorMethodName() { return 'error' }

	success(result, request) {}
	error(request) {}

	send() {
		var url = this.url,
			headers = this.headers;

		if (this.module) {
			headers['lx-type'] = 'module';
			headers['lx-module'] = this.module.name;
		}

		lx.Dialog.request({
			method: this.method,
			url: url,
			isAjax: this.isAjax,
			headers: headers,
			data: this.params,
			success: this[this.successMethodName],
			error: this[this.errorMethodName]
		});
	}

	setMethod(method) {
		this.method = method.toLowerCase();
	}

	checkMethod(method) {
		return this.method == method.toLowerCase();
	}

	setHeaders(headers) {
		this.headers = headers;
	}

	setHeader(name, value) {
		this.headers[name] = value;
	}

	setParams(params) {
		this.params = params;
	}

	setParam(params) {
		for (var name in params)
			this.params[name] = params[name];
	}

	setHandlers(handlers) {
		if (handlers.isFunction) {
			this.success = handlers;
			return;
		}

		if (handlers.success) this.success = handlers.success;
		if (handlers.error) this.error = handlers.error;
	}

	setModule(module) {
		this.module = module;
	}
}
