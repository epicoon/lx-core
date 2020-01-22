#lx:module lx.Dropbox;

#lx:use lx.Rect;
#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Table;

#lx:private;
#lx:client {
	var __opened = null;
	var __options = null;
}

class Dropbox extends lx.Box #lx:namespace lx {
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

		var wrapper = this.add(lx.Box, {
			css: this.basicCss.wrapper,
		});

		new lx.Input({
			parent: wrapper,
			key: 'input',
			css: this.basicCss.input,
		});
		if (config.placeholder) this->>input.placeholder(placeholder);

		new lx.Rect({
			parent: this,
			key: 'button',
			css: this.basicCss.button,
		});

		this.data = config.options || [];
		this.value(config.value !== undefined ? config.value : null);
	}

	#lx:client {
		postBuild(config) {
			this.data = this.data.isArray ? this.data : new lx.Dict(this.data);
			this.click(_handler_open);
			this->button.click(_handler_toggle);
		}

		postUnpack() {
			if (this.data.isObject) this.data = new lx.Dict(this.data);
		}

		close() {
			if (!__opened) return;
			__options.hide();
			lx.off('click', _lxHandler_checkOutclick);
			__opened = null;
		}

		static getOpened() {
			return __opened;
		}
	}

	getBasicCss() {
		return {
			main: 'lx-Dropbox',
			wrapper: 'lx-Dropbox-input-wrapper',
			input: 'lx-Dropbox-input',
			button: 'lx-Dropbox-but',
			option: 'lx-Dropbox-cell'
		};
	}

	/**
	 * Выбор по индексу, даже если опции - ассоциативный массив
	 * */
	select(index) {
		this.value(this.data.nthKey(index));
	}

	options(data) {
		if (data === undefined) return this.data;
		this.data = data.isAssoc ? new lx.Dict(data) : data;
		return this;
	}

	option(index) {
		return this.data.nth(index)
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

		lx.on('click', _lxHandler_checkOutclick);
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
		dropbox.trigger('change', event, oldVal, dropbox.value());
	}

	function _lxHandler_checkOutclick(event) {
		if (!__opened) return;

		if (__opened.containPoint(event.clientX, event.clientY)
			|| __options.containPoint(event.clientX, event.clientY)
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

		options.cells()
			.call('align', lx.CENTER, lx.MIDDLE)
			.call('click', _handler_choose)
			.call('addClass', self.basicCss.option);

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
