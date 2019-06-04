#lx:use lx.Box as Box;
#lx:use lx.LabeledBox as LabeledBox;

class LabeledGroup extends Box #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *	cols: integer
	 *	grid: {} | slot: {}
	 *	unit: {}  // конфиг для единицы группы
	 *	labels: []
	 * }
	 * */
	build(config) {
		super.build(config);

		var labels = config.labels,
			units = config.units,
			unitConfig = config.unit || {};

		if (config.slot) {
			config.slot.slotsCount = 0;
			this.slot(config.slot);
		} else {
			var grid = config.grid || {};
			if (!grid.cols) grid.cols = config.cols || 1;
			this.grid(grid);
		}
		this.positioningStrategy.autoActualize = false;

		unitConfig.parent = this;
		unitConfig.key = 'unit';
		unitConfig.width = 1;
		if (labels) labels.each((text)=> {
			unitConfig.label = text;
			new LabeledBox(unitConfig);
		});
		if (units) units.each((unit)=> new LabeledBox(unitConfig.lxMerge(unit)));
		if (config.slot) {
			this.positioningStrategy.autoActualize = true;
			this.positioningStrategy.actualize();
		}
	}

	units() {
		if (!this.children.unit) return new lx.Collection();
		return new lx.Collection(this.children.unit);
	}

	widgets() {
		var c = new lx.Collection();
		this.units().each((a)=> c.add(a.widget()));
		return c;
	}

	labels() {
		var c = new lx.Collection();
		this.units().each((a)=> c.add(a.label()));
		return c;
	}

	labelTexts() {
		var c = new lx.Collection();
		this.units().each((a)=> c.add(a.labelText()));
		return c;		
	}

	unit(num) {
		return this.children.unit[num];
	}

	widget(num) {
		return this.children.unit[num].widget();
	}

	label(num) {
		return this.children.unit[num].label();
	}
}
