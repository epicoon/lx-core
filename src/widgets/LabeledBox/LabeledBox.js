#lx:use lx.Box as Box;

class LabeledBox extends Box #lx:namespace lx {
	/**
	 * config = {
	 *	// стандартные для Box,
	 *	
	 *	label: string
	 *	labelPosition: lx.LEFT | lx.RIGHT | lx.TOP | lx.BOTTOM
	 *	labelAlign: [lx.CENTER, lx.MIDDLE]
	 *	labelSize: int

	 *	widget: class
	 *	widgetConfig: {}
	 *	widgetAlign: [lx.CENTER, lx.MIDDLE]
	 *	widgetSize: int

	 *	indents: config для IndentData  //todo не реализованы пользовательские отступы ни на js ни на php
	 * }
	 * */
	build(config) {
		super.build(config);

		var widget,
			label;
		if (config.template) {
			var template = config.template;
			if (template[0].isString) {
				label = template[0];
				widget = template[1];
			} else {
				label = template[1];
				widget = template[0];
			}
		}
		if (!widget) widget = config.widget || Box;
		if (!label) label = config.label || '';

		var labelPosition = config.labelPosition || lx.LEFT,
			labelConfig = {
				parent: this,
				key: 'label',
				text: label
			},
			widgetConfig = config.widgetConfig || {};

		widgetConfig.key = 'widget';
		if (labelPosition == lx.LEFT || labelPosition == lx.TOP) {		
			new Box(labelConfig);
			widgetConfig.parent = new Box({key: 'widgetBox', parent: this});
			new widget(widgetConfig);
		} else {
			widgetConfig.parent = new Box({key: 'widgetBox', parent: this});
			new widget(widgetConfig);
			new Box(labelConfig);
		}
	}

	postBuild(config) {
		var labelPosition = config.labelPosition || lx.LEFT,
			direction = lx.Geom.directionByGeom(labelPosition),
			sizeName = direction == lx.HORIZONTAL ? 'width' : 'height',
			label = this.label(),
			widgetBox = this.widgetBox(),
			widget = this.widget(),
			labelSize,
			widgetSize,
			fixLabel = false,
			fixWidget = false,
			labelAlign = config.labelAlign,
			widgetAlign = config.widgetAlign,
			subWidgetAlign = config.widgetAlign;

		// Определяется кого как выравнивать
		if (widget[sizeName]('px') == widgetBox[sizeName]('px'))
			widgetAlign = true;
		switch (labelPosition) {
			case lx.LEFT:
				if (!labelAlign) labelAlign = [lx.RIGHT, lx.MIDDLE];
				if (!widgetAlign) widgetAlign = [lx.LEFT, lx.MIDDLE];
				if (widgetAlign === true) subWidgetAlign = [lx.LEFT, lx.MIDDLE];
				break;
			case lx.RIGHT:
				if (!labelAlign) labelAlign = [lx.LEFT, lx.MIDDLE];
				if (!widgetAlign) widgetAlign = [lx.RIGHT, lx.MIDDLE];
				if (widgetAlign === true) subWidgetAlign = [lx.RIGHT, lx.MIDDLE];
				break;
			case lx.TOP:
				if (!labelAlign) labelAlign = [lx.CENTER, lx.BOTTOM];
				if (!widgetAlign) widgetAlign = [lx.CENTER, lx.TOP];
				if (widgetAlign === true) subWidgetAlign = [lx.CENTER, lx.TOP];
				break;
			case lx.BOTTOM:
				if (!labelAlign) labelAlign = [lx.CENTER, lx.TOP];
				if (!widgetAlign) widgetAlign = [lx.CENTER, lx.BOTTOM];
				if (widgetAlign === true) subWidgetAlign = [lx.CENTER, lx.BOTTOM];
				break;
		}

		/*
		todo
		лапша!!!!!!!!!!!!!!!!!
		и тудуха ниже с этим всем связана
		*/
		if (config.widgetSize && config.widgetSize.isNumber && config.labelSize && config.labelSize.isNumber) {
			widgetBox.streamProportion = config.widgetSize;
			label.streamProportion = config.labelSize;
		} else {
			/*
			Задан размер:
			1. Лэйбла
				- вычисляем размер виджета

			2. Виджета (коробки для виджета)
				- вычисляем размер лэйбла

			3. Никого
				- если сам виджет имеет какой-то размер, считаем его за размер для коробки
				- если виджет того же размера, что и коробка => нужно значение по умолчанию

			4. Обоих
				- приоритет виджету
			*/
			if (config.widgetSize) {  // 2, 4
				widgetSize = this.geomPart(config.widgetSize, 'px', direction);
				labelSize = this[sizeName]('px') - widgetSize;
				if (config.labelSize && config.labelSize.isString && config.labelSize.match(/px$/)) fixLabel = true;
			} else if (config.labelSize) {  // 1
				labelSize = this.geomPart(config.labelSize, 'px', direction);
				widgetSize = this[sizeName]('px') - labelSize;
				if (config.labelSize.isString && config.labelSize.match(/px$/)) fixLabel = true;
			} else {  // 3
				if (widget[sizeName]('px') != widgetBox[sizeName]('px')) {
					widgetSize = this.geomPart(widget[sizeName]('px'), 'px', direction);
					let wSz = widget[sizeName]();
					if (wSz === null || wSz.isString && wSz.match(/px$/))
						fixWidget = true;
				} else {
					widgetSize = this.geomPart(self::DEFAULT_WIDGET_SIZE, 'px', direction);
				}
				labelSize = this[sizeName]('px') - widgetSize;
			}

			// Если у лейбла подразумевается неизменяемый размер
			if (fixLabel) {
				label[sizeName]( labelSize + 'px' );
				widgetBox.streamProportion = 1;
			// Если у виджета подразумевается неизменяемый размер
			} else if (fixWidget) {
				widgetBox[sizeName]( widgetSize + 'px' );
				label.streamProportion = 1;
			// Если оба размера относительные
			} else {
				var k = Math.floor(Math.max(widgetSize, labelSize) / Math.min(widgetSize, labelSize));
				/*
				todo вообще надо тут все упростить. Может чисто на пропорции оставить?
				проблема:
				если блок с php поток, в него добавлен LabeledBox, то актуализация потока блока происходит после его распаковки
				соответственно этот код срабатывает, когда у виджета нет размера, у его родителя нет размера
				k получается при делении на 0
				+ тудуха выше сюда же
				*/
				if (isNaN(k)) {
					widgetSize = 13;
					labelSize = 7;
				} else if (widgetSize > labelSize) {
					widgetSize = k;
					labelSize = 1;
				} else {
					widgetSize = 1;
					labelSize = k;
				}
				widgetBox.streamProportion = widgetSize;
				label.streamProportion = labelSize;
			}
		}

		this.stream({
			direction,
			sizeBehavior: lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL
		});

		label.align(labelAlign[0], labelAlign[1]);
		if (widgetAlign !== true) widgetBox.align(widgetAlign[0], widgetAlign[1]);
		if (subWidgetAlign && subWidgetAlign.isArray) widgetBox->widget.align(subWidgetAlign[0], subWidgetAlign[1]);

		this.label().on('click', ()=> this.widget().trigger('click'));
		this.label().on('mouseup', ()=> this.widget().trigger('mouseup'));

		this.delegateListeners(config.events || ['change', 'blur']);

		super.postBuild(config);
	}

	delegateListeners(arr) {
		arr.each((a)=> this.widget().on(a, ()=> this.trigger(a)));
	}

	getIndents() {
 		return self::INDENTS.get(this);		
	}

	widget() {
		return this.children.widgetBox.children.widget;
	}

	labelText() {
		return this.children.label.children.text;
	}

	widgetBox() {
		return this.children.widgetBox;
	}

	label() {
		return this.children.label;
	}

	value(val) {
		var widget = this.widget();
		if (val === undefined) {
			if (widget.lxHasMethod('value')) return widget.value();
			if (widget.lxHasMethod('text')) return widget.text();
			return null;
		}

		if (widget.lxHasMethod('value')) widget.value(val);
		if (widget.lxHasMethod('text')) widget.text(val);
	}

	disabled(bool) {
		if (bool === undefined) return this._disabled;
		super.disabled(bool);
		this.widget().disabled(bool);
		this.label().disabled(bool);
	}
}

lx.LabeledBox.DEFAULT_WIDGET_SIZE = '65%';
// Дефолтные настройки для отступов
lx.LabeledBox.INDENTS = new lx.IndentData({
	step: '5px'
});
