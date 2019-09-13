#lx:module lx.RadioGroup;

#lx:use lx.Radio;
#lx:use lx.LabeledGroup;

class RadioGroup extends lx.LabeledGroup #lx:namespace lx {
	build(config) {
		config.widget = lx.Radio;
		config.widgetSize = '30px';
		config.labelSide = lx.RIGHT;

		super.build(config);
		this.value(config.defaultValue || 0);
	}

	#lx:client postBuild(config) {
		super.postBuild(config);

		this.widgets().each((a)=> { a.on('change', (e)=> {
			// если клик пытается снять выделение - для группы радио так нельзя - только переместить выделение на другой элемент
			if (!a.value()) {
				a.value(true);
				return;
			}

			var index = a.index,
				old = null;
			this.widgets().each((item)=> {
				if (item == a) return;
				if (item.value()) old = item.index;
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
					result = a.index;
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
