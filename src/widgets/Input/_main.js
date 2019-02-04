#use lx.Rect as Rect;

class Input extends Rect #in lx {
	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	hint: string
	 *	value: string
	 * }
	 * */
	build(config) {
		super.build(config);

		if (config.hint) this.attr('placeholder', config.hint);
		if (config.value != '') this.value(config.value);
	}

	postBuild(config) {
		super.postBuild(config);
		this.on('focus', self::setEntry );
		this.on('blur', self::unsetEntry );
	}

	tagForDOM() {
		return 'input';
	}

	value(val) {
		if (val == undefined) return this.DOMelem.value;
		this.DOMelem.value = val;
		return this;
	}

	oldValue() {
		if (this._oldValue === undefined) return null;
		return this._oldValue;
	}

	focus(func) {
		if (func == undefined) {
			lx.entryElement = this;
			this.DOMelem.focus();
			return this;
		}
		this.on('focus', func);
		return this;
	}

	valueChanged() {
		return this._oldValue != this.value();
	}

	static setEntry(event) {
		lx.entryElement = this;
		this._oldValue = this.value();
	}
	
	static unsetEntry(event) {
		lx.entryElement = null;
	}
}