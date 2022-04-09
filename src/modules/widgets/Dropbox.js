#lx:module lx.Dropbox;

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Table;

#lx:client {
	var __opened = null;
	var __options = null;
}

#lx:namespace lx;
class Dropbox extends lx.Box {
	getBasicCss() {
		return {
			main: 'lx-Dropbox',
			input: 'lx-Dropbox-input',
			button: 'lx-Dropbox-but',
			option: 'lx-Dropbox-cell'
		};
	}
	
	static initCssAsset(css) {
		css.addClass('lx-Dropbox', {
			borderRadius: css.preset.borderRadius,
			cursor: 'pointer',
			overflow: 'hidden'
		}, {
			disabled: 'opacity: 0.5'
		});
		css.addClass('lx-Dropbox-input', {
			position: 'absolute',
			width: 'calc(100% - 30px)',
			height: '100%',
			borderTopRightRadius: 0,
			borderBottomRightRadius: 0
		});
		css.addClass('lx-Dropbox-but', {
			position: 'absolute',
			right: 0,
			width: '30px',
			height: '100%',
			borderTop: '1px solid ' + css.preset.widgetBorderColor,
			borderBottom: '1px solid ' + css.preset.widgetBorderColor,
			borderRight: '1px solid ' + css.preset.widgetBorderColor,
			color: css.preset.widgetIconColor,
			background: css.preset.widgetGradient,
			cursor: 'pointer',
			'@icon': ['\\25BC', 15]
		});
		css.addClass('lx-Dropbox-cell', {
		}, {
			hover: 'background-color:' + css.preset.checkedMainColor
		});
	}

	/* params = {
	 *	// стандартные для Box,
	 *	
	 *	options: [],        // список значений 
	 *	table: params,      // конфигурации таблицы раскрывающегося списка - можно менять
	 *	value : int,        // индекс(!) активного значения из списка при инициализации
	 *	optionsCount: int,  // количество строк в таблице раскрывающегося списка
	 *	button: bool,       // будет ли отображаться кнопка "раскрыть список"
	 * }
	 * */
	build(config) {
		super.build(config);

		new lx.Input({
			parent: this,
			key: 'input',
			css: this.basicCss.input,
		});
		if (config.placeholder) this->input.placeholder(placeholder);

		new lx.Rect({
			parent: this,
			key: 'button',
			css: this.basicCss.button,
		});

		this.data = config.options || [];

		//TODO - есть встроенная возможность. По функционалу примерно то же, что сделано руками. Но и не больше.
		// if (this.data.len) {
		// 	var list = new lx.Box({tag:'datalist', parent:this});
		// 	list.setAttribute('id', 'wewe');
		// 	lx.Box.construct(this.data.len, {tag:'option', parent:list}, {
		// 		postBuild: (el, i)=>{
		// 			el.html(this.data[i]);
		// 		}
		// 	});
		// 	this->input.setAttribute('list', 'wewe');
		// }

		this.value(config.value !== undefined ? config.value : null);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);
			this.data = lx.Dict.create(this.data);
			this.click(_handler_open);
			this->button.click(_handler_toggle);
		}

		postUnpack(config) {
			super.postUnpack(config);
			this.data = lx.Dict.create(this.data);
		}

		close() {
			if (!__opened) return;
			__options.hide();
			lx.off('click', _handler_checkOutclick);
			__opened = null;
		}

		option(index) {
			return this.data.nth(index)
		}

		static getOpened() {
			return __opened;
		}
	}

	/**
	 * Выбор по индексу, даже если опции - ассоциативный массив
	 * */
	select(index) {
		this.value(this.data.nthKey(index));
	}

	options(data) {
		if (data === undefined) return this.data;
		#lx:server { this.data = data; }
		#lx:client { this.data = lx.Dict.create(data); }
		return this;
	}

	selectedText() {
		if (this.valueKey === null || this.valueKey === '') return '';

		return this.data[this.valueKey];
	}

	value(key) {
		if (key === undefined) {
			if (this.valueKey === undefined) return null;
			return this.valueKey;
		}

		this.valueKey = key;
		this->>input.value(this.selectedText());
		return this;
	}
}

/***********************************************************************************************************************
 * PRIVATE
 **********************************************************************************************************************/

#lx:client {
	function _handler_open(event) {
		event.stopPropagation();

		__opened = this;
		__initOptions(this).show();

		lx.on('click', _handler_checkOutclick);
	}

	function _handler_toggle(event) {
		event.stopPropagation();

		if (__opened === this.parent) {
			this.parent.close();
			return;
		}

		_handler_open.call(this.parent, event);
	}

	function _handler_choose(event) {
		var dropbox = __opened,
			oldVal = dropbox.value(),
			num = this.rowIndex();
		dropbox.select(num);
		dropbox.close();
		event = event || dropbox.newEvent();
		event.oldValue = oldVal;
		event.newValue = dropbox.value();
		dropbox.trigger('change', event);
	}

	function _handler_checkOutclick(event) {
		if (!__opened) return;

		if (__opened.containGlobalPoint(event.clientX, event.clientY)
			|| __options.containGlobalPoint(event.clientX, event.clientY)
		) return;

		if (__opened) __opened.close();
	}

	/**
	 * Инициализируем таблицу данными выделенного дропбокса
	 * */
	function __initOptions(self) {
		var options = __getOptions();

		options.width( self.width('px')+'px' );

		var data = [];
		for (var key in self.data) {
			data.push(self.data[key]);
		}

		options.resetContent(data, true);

		options.cells().forEach(child=>{
			child.align(lx.CENTER, lx.MIDDLE);
			child.click(_handler_choose);
			child.addClass(self.basicCss.option);
		});

		options.satelliteTo(self);

		return options;
	}

	/**
	 * Находим или создаем верстку для таблицы опций
	 * */
	function __getOptions() {
		if (__options) return __options;

		var options = lx.body->dropboxOptions;
		if (options) {
			__options = options;
			return __options;
		}

		__options = __createOptions();
		return __options;
	}

	/**
	 * Создаем верстку для таблицы опций
	 * */
	function __createOptions() {
		var tab = new lx.Table({
			parent: lx.body,
			key: 'dropboxOptions',
			geom: true,
			cols: 1
		}).style('z-index', 1001).hide();
		return tab;
	}
}
