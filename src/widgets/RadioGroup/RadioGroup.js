#lx:use lx.LabeledGroup as LabeledGroup;

class RadioGroup extends LabeledGroup #lx:namespace lx {
	preBuild(config) {
		if (!config.unit) config.unit = {};
		config.unit.widget = lx.Radio;
		if (config.unit.labelPosition === undefined)
			config.unit.labelPosition = lx.RIGHT;

		return super.preBuild(config);
	}

	build(config) {
		super.build(config);

		this.value(config.defaultValue || 0);
	}

	postBuild(config) {
		super.postBuild(config);

		this.widgets().each((a)=> { a.on('change', (e)=> {
			// если клик пытается снять выделение - для группы радио так нельзя - только переместить выделение на другой элемент
			if (!a.value()) {
				a.value(true);
				return;
			}

			var index = a.parent.parent.index,
				old = null;
			this.widgets().each((item)=> {
				if (item == a) return;
				if (item.value()) old = item.parent.parent.index;
				item.value(false);
			});
			this.trigger('change', e, index, old);
		})});
	}

	value(num) {
		if (num === undefined) {
			var result = null;
			this.widgets().each(function(a) {
				if (a.value()) {
					result = a.parent.parent.index;
					this.stop();
				}
			});
			return result;
		}

		if (!num) num = 0;

		this.widgets().each((a)=> a.value(false));
		this.widget(num).value(true);
	}
}
