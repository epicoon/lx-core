#lx:module lx.CheckboxGroup;

#lx:use lx.Checkbox;
#lx:use lx.LabeledGroup;

class CheckboxGroup extends lx.LabeledGroup #lx:namespace lx {
	build(config) {
		config.widget = lx.Checkbox;
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		if (config.defaultValue !== undefined) this.value(config.defaultValue);
	}

	#lx:client postBuild(config) {
		super.postBuild(config);

		this.widgets().each((a)=> { a.on('change', (e)=> {
			this.trigger('change', e, a.index, a.value());
		})});
	}

	value(nums) {
		if (nums === undefined) {
			var result = [];
			this.widgets().each(function(a) {
				if (a.value()) result.push(a.index);
			});

			return result;
		}

		if (!nums) nums = [];

		this.widgets().each((a)=> a.value(false));
		if (!nums.isArray) nums = [nums];
		nums.each((num)=> this.widget(num).value(true));
	}
}
