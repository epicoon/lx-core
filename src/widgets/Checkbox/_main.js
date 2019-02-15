#lx:use lx.Rect as Rect;

class Checkbox extends Rect #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	value: boolean
	 * }
	 * */
	build(config) {
		super.build(config);
		this.value(config.value || false);
	}

	postBuild(config) {
		super.postBuild(config);
		this.on('mousedown', lx.Event.preventDefault);
		this.on('mouseup', self::click);
	}

	getBaseCss() {
		return (this.disabled())
			? this.getDisabledClass() + '-' + +this.value()
			: this.getEnabledClass() + '-' + +this.value();
	}

	value(val) {
		if (val === undefined) return this.state;

		this.removeClass( this.getBaseCss() );
		this.state = !!val;
		this.addClass( this.getBaseCss() );

		return this;
	}

	todgle() {
		this.value(!this.value());
		return this;
	}

	static click(event) {
		this.value( !this.value() );
		this.trigger('change', event);
	}
}