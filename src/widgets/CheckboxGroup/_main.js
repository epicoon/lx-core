#use lx.LabeledGroup as LabeledGroup;

class CheckboxGroup extends LabeledGroup #in lx {
	preBuild(config) {
		if (!config.unit) config.unit = {};
		config.unit.widget = lx.Checkbox;
		if (config.unit.labelPosition === undefined)
			config.unit.labelPosition = lx.RIGHT;

		return super.preBuild(config);
	}

	build(config) {
		super.build(config);
		if (config.defaultValue !== undefined) this.value(config.defaultValue);
	}

	postBuild(config) {
		super.postBuild(config);

		this.widgets().each((a)=> { a.on('change', (e)=> {
			this.trigger('change', e, a.parent.parent.index, a.value());
		})});
	}

	value(nums) {
		if (nums === undefined) {
			var result = [];
			this.widgets().each(function(a) {
				if (a.value()) result.push(a.parent.parent.index);
			});
			return result;
		}

		if (!nums) nums = [];

		this.widgets().each((a)=> a.value(false));
		if (!nums.isArray) nums = [nums];
		nums.each((num)=> this.widget(num).value(true));
	}
}