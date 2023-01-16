#lx:module lx.Checkbox;

#lx:use lx.Box;

/**
 * @widget lx.Checkbox
 * @content-disallowed
 */
#lx:namespace lx;
class Checkbox extends lx.Box {
	getBasicCss() {
		return {
			checked: 'lx-Checkbox-1',
			unchecked: 'lx-Checkbox-0'
		};
	}
	
	static initCss(css) {
		css.addClass('lx-Checkbox-0', {
			border: 'solid #61615e 1px',
			width: '16px',
			height: '16px',
			borderRadius: '4px',
			backgroundColor: 'white',
			cursor: 'pointer'
		}, {
			hover: {
				boxShadow: '0 0 6px ' + css.preset.widgetIconColor,
			},
			active: {
				backgroundColor: '#dedede',
				boxShadow: '0 0 8px ' + css.preset.widgetIconColor,
			}
		});
		css.inheritClass('lx-Checkbox-1', 'lx-Checkbox-0', {
			color: 'black',
			'@icon': ['\\2713', {fontSize:8, fontWeight:600, paddingLeft:'1px', paddingBottom:'0px'}],
		});
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [value = false] {Boolean}
	 * }}
	 */
	build(config) {
		super.build(config);
		this.add(lx.Box, {
			key: 'check',
			coords: [0, 0],
			// geom: [0, 0, '24px', '24px']
		});
		this.align(lx.CENTER, lx.MIDDLE);
		this.value(config.value || false);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);
			this.on('mousedown', lx.preventDefault);
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