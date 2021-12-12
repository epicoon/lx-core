class HttpRequest extends lx.Request #lx:namespace lx {
	constructor(url = '', params = {}) {
		super();

		this.method = 'post';
		this.url = url;

		this.headers = {};
		this.params = params;
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

	send() {
		var url = this.url,
			headers = this.headers;

		lx.Dialog.request({
			method: this.method,
			url: url,
			headers: headers,
			data: this.params,
			success: [this, __onSuccess],
			waiting: [this, __onWait],
			error: [this, __onError]
		});

		return this;
	}
}

function __onSuccess(result, request) {
	if (lx.isFunction(this._success))
		this._success.call(this, result, request);
}

function __onWait(request) {
	if (lx.isFunction(this._wait))
		this._wait.call(this, request);
}

function __onError(request) {
	if (lx.isFunction(this._error))
		this._error.call(this, request);
}
