#lx:module lx.Checkbox;

#lx:use lx.Rect;

class Checkbox extends lx.Rect #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	value: bool
	 * }
	 * */
	build(config) {
		super.build(config);
		this.value(config.value || false);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);
			this.on('mousedown', lx.Event.preventDefault);
			this.on('mouseup', self::click);
		}

		static click(event) {
			this.value( !this.value() );
			this.trigger('change', event);
		}
	}

	getBasicCss() {
		return {
			checked: 'lx-Checkbox-1',
			unchecked: 'lx-Checkbox-0'
		};
	}

	value(val) {
		if (val === undefined) return this.state;

		this.state = !!val;
		this.removeClass(this.basicCss.checked);
		this.removeClass(this.basicCss.unchecked);
		if (this.state) this.addClass(this.basicCss.checked);
		else this.addClass(this.basicCss.unchecked);

		return this;
	}

	todgle() {
		this.value(!this.value());
		return this;
	}
}