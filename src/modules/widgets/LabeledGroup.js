#lx:module lx.LabeledGroup;

#lx:use lx.Box;

#lx:namespace lx;
class LabeledGroup extends lx.Box {
	getBasicCss() {
		return {
			main: 'lx-LabeledGroup',
			item: 'lx-LabeledGroup-item',
			label: 'lx-LabeledGroup-label'
		};
	}
	
	static initCssAsset(css) {
		css.addClass('lx-LabeledGroup', {
			display: 'grid',
			gridAutoFlow: 'row',
			gridGap: '.8em'
		});
		css.addClass('lx-LabeledGroup-item', {
			position: 'relative',
			gridRow: 'auto'
		});
		css.addClass('lx-LabeledGroup-label', {
		});
	}

	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *	cols: int
	 *  step: int
	 *	widget: class
	 *	widgetSize: string
	 *	labelSide: lx.LEFT | lx.RIGHT
	 *	labels: []
	 *	list: []
	 *  fields: {}
	 * }
	 * */
	build(config) {
		super.build(config);

		var labelSide = config.labelSide || lx.LEFT;
		var template = '';
		var cols = config.cols || 1;
		var widgetSize = config.widgetSize || '1fr';
		if (labelSide == lx.LEFT) {
			for (var i=0; i<cols; i++)
				template += '[labels'+i+']auto[controls'+i+']'+widgetSize;
		} else {
			for (var i=0; i<cols; i++)
				template += '[controls'+i+']'+widgetSize+'[labels'+i+']auto';
		}
		this.style('grid-template-columns', template);
		if (config.step) {
			this.style('grid-gap', config.step);
		}

		let defaultWidget = config.widget || lx.Box;
		let units = [];
		if (config.list) {
			for (let i=0, l=config.list.len; i<l; i++) {
				let unitConfig = config.list[i];
				let unitWidget = unitConfig.widget || defaultWidget;
				delete unitConfig.widget;
				let labelConfig = {};
				labelConfig.text = unitConfig.label || '-';
				delete unitConfig.label;
				units.push({widget: unitWidget, config: unitConfig, labelConfig});
			}
		} else if (config.fields) {
			for (let field in config.fields) {
				let unitConfig = config.fields[field];
				let unitWidget = unitConfig.widget || defaultWidget;
				delete unitConfig.widget;
				let labelConfig = {};
				labelConfig.text = unitConfig.label || '-';
				delete unitConfig.label;
				unitConfig.field = field;
				units.push({widget: unitWidget, config: unitConfig, labelConfig});
			}
		} else if (config.labels) {
			for (let i=0, l=config.labels.len; i<l; i++)
				units.push({
					widget: defaultWidget,
					config: {},
					labelConfig: {text: config.labels[i]}
				});
		}

		var counter = 0;
		for (let i=0, l=units.len; i<l; i++) {
			let unit = units[i];
			let widget = unit.widget;
			let widgetConfig = unit.config.lxMerge({
				parent: this,
				key: 'widget',
				css: [this.basicCss.item],
				style: {'grid-column': 'controls' + counter}
			});
			let labelConfig = unit.labelConfig.lxMerge({
				parent: this,
				key: 'label',
				css: [this.basicCss.item, this.basicCss.label],
				style: {'grid-column': 'labels' + counter}
			});
			if (labelSide == lx.LEFT) {
				(new lx.Box(labelConfig)).align(lx.CENTER, lx.MIDDLE);
				new widget(widgetConfig);
			} else {
				new widget(widgetConfig);
				(new lx.Box(labelConfig)).align(lx.CENTER, lx.MIDDLE);
			}
			counter++;
			if (counter >= cols) counter = 0;
		}
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		if (!this->label) return;
		this.getAll('label').forEach(l=>{
			l.on('click', function() {
				this.parent.widget(this.index).trigger('click');
			});
			l.on('mouseup', function() {
				this.parent.widget(this.index).trigger('mouseup');
			});
		});
	}

	align(w, h) {
		this.labels().forEach(a=>a.align(w, h));
	}

	widgets() {
		if (!this->widget) return new lx.Collection();
		return new lx.Collection(this->widget);
	}

	labels() {
		if (!this->label) return new lx.Collection();
		return new lx.Collection(this->label);
	}

	widget(num) {
		if (!this->widget) return null;
		return this.getAll('widget').at(num);
	}

	label(num) {
		if (!this->label) return null;
		return this.getAll('label').at(num);
	}
}
