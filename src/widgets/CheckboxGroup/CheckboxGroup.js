#lx:module lx.CheckboxGroup;

#lx:use lx.Checkbox;
#lx:use lx.LabeledGroup;

#lx:private;

class CheckboxGroup extends lx.LabeledGroup #lx:namespace lx {
	build(config) {
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		this.widgets().each(w=>{
			let checkbox = w.add(lx.Checkbox, {key: 'checkbox'});
			w.align(lx.CENTER, lx.MIDDLE);
		});
		if (config.defaultValue !== undefined) this.value(config.defaultValue);
	}

	#lx:client postBuild(config) {
		super.postBuild(config);
		this.checkboxes().each((a)=>a.on('change', _handler_onChange));
		this.labels().each(l=>{
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
			this.checkboxes().each(function(a) {
				if (a.value()) result.push(a.index);
			});

			return result;
		}

		if (!nums) nums = [];

		this.checkboxes().each((a)=> a.value(false));
		if (!nums.isArray) nums = [nums];
		nums.each((num)=> this.widget(num).value(true));
	}
}


/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

#lx:client {
	function _handler_onChange(e) {
		var group = this.parent.parent;
		group.trigger('change', e, this.parent.index, this.value());
	}
}
