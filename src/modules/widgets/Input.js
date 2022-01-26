#lx:module lx.Input;

#lx:use lx.Rect;

class Input extends lx.Rect #lx:namespace lx {
	getBasicCss() {
		return 'lx-Input';
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Input', 'Input', {
		}, {
			focus: 'border: 1px solid ' + css.preset.checkedMainColor,
			disabled: 'opacity: 0.5'
		});
	}

	static getStaticTag() {
		return 'input';
	}

	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	placeholder: string
	 *	value: string
	 * }
	 * */
	build(config) {
		super.build(config);

		if (config.placeholder) this.setAttribute('placeholder', config.placeholder);
		if (config.value != '') this.value(config.value);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);
			this.on('focus', self::setEntry );
			this.on('blur', self::unsetEntry );
		}

		static setEntry(event) {
			lx.entryElement = this;
			this._oldValue = this.value();
		}

		static unsetEntry(event) {
			lx.entryElement = null;
		}

		valueChanged() {
			return this._oldValue != this.value();
		}

		oldValue() {
			if (this._oldValue === undefined) return null;
			return this._oldValue;
		}
	}

	value(val) {
		#lx:server {
			if (val == undefined) return this.getAttribute('value');
			this.setAttribute('value', val);
		}

		#lx:client {
			if (val == undefined) return this.domElem.param('value');
			this.domElem.param('value', val);
		}

		return this;
	}

	placeholder(val) {
		if (val === undefined) return this.getAttribute('placeholder');
		this.setAttribute('placeholder');
	}

	focus(func) {
		#lx:client{ if (func === undefined) {
			var elem = this.getDomElem();
			if (elem) elem.focus();
			return this;
		}}
		this.on('focus', func);
		return this;
	}

	blur(func) {
		#lx:client{ if (func === undefined) {
			var elem = this.getDomElem();
			if (elem) elem.blur();
			return this;
		}}
		this.on('blur', func);
		return this;
	}
}
