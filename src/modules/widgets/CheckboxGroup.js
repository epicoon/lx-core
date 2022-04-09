#lx:module lx.CheckboxGroup;

#lx:use lx.Checkbox;
#lx:use lx.LabeledGroup;

#lx:namespace lx;
class CheckboxGroup extends lx.LabeledGroup {
	build(config) {
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		this.widgets().forEach(w=>{
			let checkbox = w.add(lx.Checkbox, {key: 'checkbox'});
			w.align(lx.CENTER, lx.MIDDLE);
			if (w._field) {
				checkbox._field = w._field;
				delete w._field;
			}
		});
		if (config.defaultValue !== undefined) this.value(config.defaultValue);
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		this.checkboxes().forEach(a=>a.on('change', _handler_onChange));
		this.labels().forEach(l=>{
			l.style('cursor', 'pointer');
			l.on('mousedown', lx.Event.preventDefault);
			l.on('click', (e)=>{
				let checkbox = this.checkbox(l.index);
				checkbox.todgle();
				_handler_onChange.call(checkbox, e);
			});
		});
	}

	checkboxes() {
		return this.findAll('checkbox');
	}

	checkbox(num) {
		return this.widget(num)->checkbox;
	}

	value(nums) {
		if (nums === undefined) {
			var result = [];
			this.checkboxes().forEach(function(a) {
				if (a.value()) result.push(a.index);
			});

			return result;
		}

		if (!nums) nums = [];

		this.checkboxes().forEach(a=>a.value(false));
		if (!lx.isArray(nums)) nums = [nums];
		nums.forEach(num=>this.checkbox(num).value(true));
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

#lx:client {
	function _handler_onChange(e) {
		var group = this.parent.parent;
		e = e || group.newEvent();
		e.changedIndex = this.parent.index;
		e.currentValue = this.value();
		e.currentValues = this.ancestor({is:lx.CheckboxGroup}).value();
		group.trigger('change', e);
	}
}
