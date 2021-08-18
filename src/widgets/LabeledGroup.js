#lx:module lx.LabeledGroup;

#lx:use lx.Box;

class LabeledGroup extends lx.Box #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *	cols: integer
	 *	widget: class
	 *	widgetSize: string
	 *	labelSide: lx.LEFT | lx.RIGHT
	 *	labels: []
	 *	map: {}
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

		var widget = config.widget || lx.Box;
		var map = {};
		for (var i in config) {
			if (i == 'labels') {
				for (var j=0, l=config[i].len; j<l; j++) map[config[i][j]] = widget;
			} else if (i == 'map') {
				for (var label in config[i]) map[label] = config[i][label];
			}
		}

		var counter = 0;
		for (var label in map) {
			var l, w;
			if (labelSide == lx.LEFT) {
				l = new lx.Box({
					parent: this,
					key: 'label',
					css: [this.basicCss.item, this.basicCss.label],
					style: {'grid-column': 'labels' + counter}
				});
				w = new map[label]({
					parent: this,
					key: 'widget',
					css: [this.basicCss.item],
					style: {'grid-column': 'controls' + counter}
				});
			} else {
				w = new map[label]({
					parent: this,
					key: 'widget',
					css: [this.basicCss.item],
					style: {'grid-column': 'controls' + counter}
				});
				l = new lx.Box({
					parent: this,
					key: 'label',
					css: [this.basicCss.item, this.basicCss.label],
					style: {'grid-column': 'labels' + counter}
				});
			}
			l.text(label);
			counter++;
			if (counter >= cols) counter = 0;
		}
	}

	getBasicCss() {
		return {
			main: 'lx-LabeledGroup',
			item: 'lx-LabeledGroup-item',
			label: 'lx-LabeledGroup-label'
		};
	}

	#lx:client clientBuild(config) {
		super.clientBuild(config);
		if (!this->label) return;
		this->label.each((l)=>{
			l.on('click', function() {
				this.parent.widget(this.index).trigger('click');
			});
			l.on('mouseup', function() {
				this.parent.widget(this.index).trigger('mouseup');
			});
		});
	}

	align(w, h) {
		this.labels().each(a=>a.align(w, h));
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
		return this->widget[num];
	}

	label(num) {
		if (!this->label) return null;
		return this->label[num];
	}
}
