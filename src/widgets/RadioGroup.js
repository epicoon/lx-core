#lx:module lx.RadioGroup;

#lx:use lx.Radio;
#lx:use lx.LabeledGroup;

#lx:private;

class RadioGroup extends lx.LabeledGroup #lx:namespace lx {
	build(config) {
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		this.widgets().forEach(w=>{
			let radio = w.add(lx.Radio, {key: 'radio'});
			w.align(lx.CENTER, lx.MIDDLE);
		});
		this.value(config.defaultValue || 0);
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		this.radios().forEach(a=>a.on('change', _handler_onChange));
		this.labels().forEach(l=>{
			l.style('cursor', 'pointer');
			l.on('mousedown', lx.Event.preventDefault);
			l.on('click', (e)=>{
				let radio = this.radio(l.index);
				radio.todgle();
				_handler_onChange.call(radio, e);
			});
		});
	}

	radios() {
		return this.findAll('radio');
	}

	radio(num) {
		return this.widget(num)->radio;
	}

	value(num) {
		if (num === undefined) {
			var result = null;
			this.radios().forEach(function(a) {
				if (a.value()) {
					result = a.index;
					this.stop();
				}
			});
			return result;
		}

		if (!num) num = 0;

		this.radios().forEach(a=>a.value(false));
		this.radio(num).value(true);
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

#lx:client {
	function _handler_onChange(e) {
		// если клик пытается снять выделение - для группы радио так нельзя
		// только переместить выделение на другой элемент
		if (!this.value()) {
			this.value(true);
			return;
		}

		var group = this.parent.parent,
			index = this.parent.index,
			old = null;
		group.radios().forEach(item=>{
			if (item == this) return;
			if (item.value()) old = item.parent.index;
			item.value(false);
		});
		group.trigger('change', e, index, old);
	}
}
