class Request #lx:namespace lx {
	constructor(url = '', params = {}) {
		this.method = 'post';
		this.url = url;

		this.headers = {};
		this.params = params;

		this.handler = null;
	}

	get successMethodName() { return 'success' }
	get errorMethodName() { return 'error' }

	success(result, request) {}
	error(request) {}

	then(func) {
		this.handler.success = func;
		return this;
	}

	catch(func) {
		this.handler.error = func;
		return this;
	}

	send() {
		var url = this.url,
			headers = this.headers;

		this.handler = lx.Dialog.request({
			method: this.method,
			url: url,
			headers: headers,
			data: this.params,
			success: this[this.successMethodName],
			error: this[this.errorMethodName]
		});

		return this;
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
		if (lx.isFunction(handlers)) {
			this.success = handlers;
			return;
		}

		if (handlers.success) this.success = handlers.success;
		if (handlers.error) this.error = handlers.error;
	}
}
