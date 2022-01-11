class Css #lx:namespace lx {
	constructor(factor) {
		if (lx.isString(factor)) {
			var tag = document.getElementById(factor);
			if (!tag) {
				tag = document.createElement('style');
				tag.setAttribute('id', factor);
				var head = document.getElementsByTagName('head')[0];
				head.appendChild(tag);
			}
			factor = {tag};
		}

		if (lx.isObject(factor)) {
			this.tag = factor.tag;
		}

		this._context = new lx.CssContext();
	}

	static exists(name) {
		return !!document.getElementById(name);
	}

	get context() {
		return this._context;
	}

	commit() {
		this.tag.innerHTML = this._context.toString();
	}
}
