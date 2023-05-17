#lx:module lx.RadioGroup;

#lx:use lx.Radio;
#lx:use lx.LabeledGroup;

/**
 * @widget lx.RadioGroup
 * @content-disallowed
 */
#lx:namespace lx;
class RadioGroup extends lx.LabeledGroup {
	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [defaultValue = 0] {Number}
	 * }}
	 */
	build(config) {
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		this.widgets().forEach(w=>{
			let radio = w.add(lx.Radio, {key: 'radio'});
			w.align(lx.CENTER, lx.MIDDLE);
		});
		this._value = 0;
		this.value(config.defaultValue || 0);
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		this.radios().forEach(r=>{
			r.on('click', ()=>_handler_onChange(this, r));
		});
		this.labels().forEach(l=>{
			l.style('cursor', 'pointer');
			l.on('mousedown', lx.preventDefault);
			l.on('click', ()=>_handler_onChange(this, this.radio(l.index)));
		});
	}

	radios() {
		return this.findAll('radio');
	}

	radio(num) {
		return this.widget(num)->radio;
	}

	value(num) {
		if (num === undefined) return this._value;

		if (!num) num = 0;
		this.radios().forEach(a=>a.value(false));
		this.radio(num).value(true);
		this._value = num;
	}

	disabled(val) {
		this.radios().forEach(r=>r.disabled(val));
		this.labels().forEach(l=>l.disabled(val));
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

#lx:client {
	function _handler_onChange(group, radio) {
		let index = radio.parent.index;
		if (index == group.value()) {
			radio.value(true);
			return;
		}

		let oldValue = group.value();
		radio.value(false);
		group.value(index);
		group.trigger('change', group.newEvent({
			oldValue,
			newValue: index
		}));
	}
}
