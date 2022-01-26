class Css #lx:namespace lx {
	#lx:const
		POSITION_TOP = 'top',
		POSITION_BOTTOM = 'bottom';

	constructor(factor, position = null) {
		if (position === null) position = self::POSITION_BOTTOM;

		if (lx.isString(factor)) {
			var tag = document.getElementById(factor);
			if (!tag) {
				tag = document.createElement('style');
				tag.setAttribute('id', factor);
				var head = document.getElementsByTagName('head')[0];
				if (position == self::POSITION_BOTTOM) head.appendChild(tag);
				else head.insertBefore(tag, head.querySelector('link[name=base_css]'));
			}
			factor = {tag};
		}

		if (lx.isObject(factor)) {
			this.tag = factor.tag;
		}

		this._asset = new lx.CssAsset();
	}

	static exists(name) {
		return !!document.getElementById(name);
	}

	getAsset() {
		return this._asset;
	}

	commit() {
		this.tag.innerHTML = this._asset.toString();
	}
}
