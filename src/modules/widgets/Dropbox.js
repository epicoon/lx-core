#lx:module lx.Dropbox;

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Table;

#lx:client {
	var __opened = null;
	var __options = null;
}

/**
 * @widget lx.Dropbox
 * @content-disallowed
 * 
 * @events [
 *     change,
 *     opened,
 *     closed
 * ]
 */
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
	
	static initCss(css) {
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

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [placeholder] {String},
	 *     [options] {Array<String|Number>|Dict<String|Number>},
	 *     [value = null] {Number|String} (: active value key :),
	 *     [button] {Boolean} (: Flag for rendering open-close button :)
	 * }}
	 */
	build(config) {
		super.build(config);

		new lx.Input({
			parent: this,
			key: 'input',
			css: this.basicCss.input,
		});
		if (config.placeholder) this->input.placeholder(placeholder);

		let button = (config.button === undefined) ? true : config.button;
		if (button)
			new lx.Rect({
				parent: this,
				key: 'button',
				css: this.basicCss.button,
			});

		this._options = config.options || [];
		this.value(config.value !== undefined ? config.value : null);
	}

	#lx:client {
		clientBuild(config) {
			super.clientBuild(config);
			this._options = lx.Dict.create(this._options);
			this.click(_handler_open);
			if (this.contains('button'))
				this->button.click(_handler_toggle);
		}

		postUnpack(config) {
			super.postUnpack(config);
			this._options = lx.Dict.create(this._options);
		}

		close() {
			if (!__opened) return;
			__options.hide();
			lx.off('click', _handler_checkOutclick);
			__opened = null;
			this.trigger('closed');
		}

		option(index) {
			return this._options.nth(index)
		}

		static getOpened() {
			return __opened;
		}
	}

	/**
	 * Выбор по индексу, даже если опции - ассоциативный массив
	 * */
	select(index) {
		this.value(this._options.nthKey(index));
	}

	options(options) {
		if (options === undefined) return this._options;
		#lx:server { this._options = options; }
		#lx:client { this._options = lx.Dict.create(options); }
		return this;
	}

	selectedText() {
		if (this.valueKey === null || this.valueKey === '') return '';

		return this._options[this.valueKey];
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

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

#lx:client {
	function _handler_open(event) {
		event.stopPropagation();

		__opened = this;
		__initOptions(this).show();
		this.trigger('opened', event);

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
		event = event || dropbox.newEvent();
		event.oldValue = oldVal;
		event.newValue = dropbox.value();
		dropbox.trigger('change', event);
		dropbox.close();
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
	 */
	function __initOptions(self) {
		var optionsTable = __getOptions();

		optionsTable.width( self.width('px')+'px' );

		var options = [];
		for (let key in self._options) {
			options.push(self._options[key]);
		}

		optionsTable.resetContent(options, true);

		optionsTable.cells().forEach(child=>{
			child.align(lx.CENTER, lx.MIDDLE);
			child.click(_handler_choose);
			child.addClass(self.basicCss.option);
		});

		optionsTable.satelliteTo(self);

		return optionsTable;
	}

	/**
	 * Находим или создаем верстку для таблицы опций
	 */
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
	 */
	function __createOptions() {
		var tab = new lx.Table({
			parent: lx.body,
			key: 'dropboxOptions',
			geom: true,
			cols: 1,
			depthCluster: lx.DepthClusterMap.CLUSTER_OVER,
		}).hide();
		return tab;
	}
}
