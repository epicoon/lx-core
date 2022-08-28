#lx:namespace lx;
class HttpRequest extends lx.Request {
	constructor(url = '', params = {}) {
		super();

		this.method = 'post';
		this.url = url;

		this.headers = {};
		this.params = params;
	}

	setMethod(method) {
		this.method = method.toLowerCase();
		return this;
	}

	checkMethod(method) {
		return this.method == method.toLowerCase();
	}

	setHeaders(headers) {
		this.headers = headers;
		return this;
	}

	setHeader(name, value) {
		this.headers[name] = value;
		return this;
	}

	setParams(params) {
		this.params = params;
		return this;
	}

	setParam(params) {
		for (var name in params)
			this.params[name] = params[name];
		return this;
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
