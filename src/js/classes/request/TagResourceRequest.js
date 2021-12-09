class TagResourceRequest extends lx.Request #lx:namespace lx {
	constructor(url = '', attributes = {}) {
		super();

		this.url = url;
		this.attributes = attributes;
		this.location = 'head';
	}

	static createByConfig(config) {
		const request = new this();

		request.url = config.path;
		request.attributes = config.attributes || {};
		request.location = config.location || 'head';
		if (config.onLoad) {
			if (lx.isString(config.onLoad))
				config.onLoad = lx._f.createFunction(config.onLoad);
			this.onLoad(config.onLoad);
		}
		if (config.onError) {
			if (lx.isString(config.onError))
				config.onError = lx._f.createFunction(config.onError);
			this.onError(config.onError);
		}

		return request;
	}

	send() {
		let tag = null;
		if (this.url.match(/\.js$/)) {
			tag = document.createElement('script');
			tag.src = this.url;
		} else {
			tag  = document.createElement('link');
			tag.rel  = 'stylesheet';
			tag.type = 'text/css';
			tag.href = this.url;
		}

		for (let name in this.attributes)
			tag.setAttribute(name, this.attributes[name]);

		tag.onload = __onLoad.bind(this);
		tag.onerror = __onError.bind(this);

		switch (this.location) {
			case 'head':
				var head = document.getElementsByTagName('head')[0];
				head.appendChild(tag);
				break;
			case 'body-top':
				var body = document.getElementsByTagName('body')[0];
				if (body.children.length)
					body.insertBefore(tag, body.children[0]);
				else body.appendChild(tag);
				break;
			case 'body-bottom':
				var body = document.getElementsByTagName('body')[0];
				body.appendChild(tag);
				break;
		}

		return this;
	}

}

function __onLoad() {
	if (lx.isFunction(this._success))
		this._success.call(this);
}

function __onError() {
	if (lx.isFunction(this._error))
		this._error.call(this);
}
