#lx:module lx.Checkbox;

#lx:use lx.Box;

#lx:namespace lx;
class Checkbox extends lx.Box {
	getBasicCss() {
		return {
			checked: 'lx-Checkbox-1',
			unchecked: 'lx-Checkbox-0'
		};
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Checkbox-0', 'Checkbox-shape', {
			backgroundPosition: '-2px -3px'
		}, {
			hover: 'background-position: -46px -3px',
			active: 'background-position: -70px -3px',
			disabled: 'background-position: -184px -3px'
		});
		css.inheritClass('lx-Checkbox-1', 'Checkbox-shape', {
			backgroundPosition: '-92px -3px'
		}, {
			hover: 'background-position: -135px -3px',
			active: 'background-position: -160px -3px',
			disabled: 'background-position: -206px -3px'
		});
	}

	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	value: bool
	 * }
	 * */
	build(config) {
		super.build(config);
		this.add(lx.Box, {
			key: 'check',
			geom: [0, 0, '24px', '24px']
		});
		this.align(lx.CENTER, lx.MIDDLE);
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

	value(val) {
		if (val === undefined) return this.state;

		this.state = !!val;
		this->check.removeClass(this.basicCss.checked);
		this->check.removeClass(this.basicCss.unchecked);
		if (this.state) this->check.addClass(this.basicCss.checked);
		else this->check.addClass(this.basicCss.unchecked);

		return this;
	}

	todgle() {
		this.value(!this.value());
		this.trigger('change', this.newEvent({
			oldValue: !this.value(),
			newValue: this.value()
		}));
		return this;
	}
}