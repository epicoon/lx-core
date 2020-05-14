class Css #lx:namespace lx {
	constructor(factor) {
		if (factor.isString) {
			var tag = document.getElementById(factor);
			if (!tag) {
				tag = document.createElement('style');
				var head = document.getElementsByTagName('head')[0];
				head.appendChild(tag);
				tag.setAttribute('id', factor);
			}
			factor = {tag};
		}

		if (factor.isObject) {
			this.tag = factor.tag;
		}

		this.context = new lx.CssContext();
	}

	static exists(name) {
		return !!document.getElementById(name);
	}

	commit() {
		this.tag.innerHTML = this.context.toString();
	}
}
