#lx:namespace lx;
class CssTag {
	constructor(config) {
		this._context = new lx.CssContext();
		this._context.configure({
			proxyContexts: config.proxyContexts || lx.app.cssManager.getProxyContexts(),
			preset: config.preset || lx.app.cssManager.getPreset()
		});

		this.domElem = null;
		if (config.id) {
			var elem = document.getElementById(config.id);
			if (!elem) {
				elem = document.createElement('style');
				elem.setAttribute('id', config.id);
				let head = document.getElementsByTagName('head')[0];
				let before = null;
				if (config.before) before = head.querySelector(config.before);
				else if (config.after) {
					let after = head.querySelector(config.after);
					if (after) before = after.nextSibling;
				}
				if (before === null) head.appendChild(elem);
				else head.insertBefore(elem, before);
			}
			config.domElem = elem;
		}
		if (config.domElem) this.domElem = config.domElem;
	}

	static exists(id) {
		return !!document.getElementById(id);
	}

	getContext() {
		return this._context;
	}

	usePreset(preset) {
		this._context.usePreset(preset);
	}

	commit() {
		this.domElem.innerHTML = this._context.toString();
	}
}
