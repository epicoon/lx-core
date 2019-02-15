#lx:use lx.Rect as Rect;
#lx:use lx.Box as Box;
#lx:use lx.Table as Table;

class Dropbox extends Box #lx:namespace lx {
	/* params = {
	 *	// стандартные для Box,
	 *	
	 *	options: [],           // список значений 
	 *	table: params,      // конфигурации таблицы раскрывающегося списка - можно менять
	 *	value : int,        // индекс(!) активного значения из списка при инициализации
	 *	optionsCount: int,  // количество строк в таблице раскрывающегося списка
	 *	button: bool,       // будет ли отображаться кнопка "раскрыть список"
	 *	animation: bool     // раскрытие с анимацией, todo!!!
	 * }
	 * */
	build(config) {
		super.build(config);

		this.overflow('visible');

		var h = this.height('px');
		if (config.button !== false) {
			var but = new Rect({
				parent: this,
				key: 'but',
				css: 'lx-Dropbox-but' 
			});
		}

		this.keys = [];
		this.data = [];
		this.options(config.options || []);
		this.value(config.value !== undefined ? config.value : null);
	}

	postBuild(config) {
		if (this.children.but)
			this.children.but.width(this.height('px')+'px');
		this.children.but.right(0);
		this.click(self::open);
	}
	
	options(data) {
		if (data === undefined) return this.data;

		this.keys = [];
		this.data = [];
		this.addOptions(data);
		return this;
	}

	option(num) {
		return this.children.options.cell(num, 0);
	}

	selectedText() {
		if (this.val === null || this.val === '') return '';

		if (this.keys.length) return this.data[this.keys.indexOf(''+this.val)];
		return this.data[this.val];
	}

	selectedKey() {
		if (this.keys.lxEmpty) return this.val;
		return this.keys[this.val];
	}

	value(val) {
		if (val === undefined) return this.selectedKey();

		this.val = val;
		this.text(this.selectedText());
		return this;
	}

	addOptions(data) {
		//todo что если начнется смешивание ассоциативных и неассоциативных массивов?
		if (!data.isAssoc) {
			this.data = data;
			return this;
		}

		for (var key in data) {
			this.data.push(data[key]);
			this.keys.push(key);
		}
		return this;
	}

	close() {
		if (!self::opened) return;
		self::options.hide();
		lx.off('click', [this, self::outclick]);
		self::opened = null;
	}

	static choose(event) {
		var dropbox = lx.Dropbox.opened,
			oldVal = dropbox.value(),
			num = this.rowIndex();
		dropbox.value(num);
		dropbox.trigger('change', event, oldVal, num);
	}

	static outclick() {
		if (self::opened) self::opened.close();
	}

	static open(event) {
		event.stopPropagation();

		self::opened = this;
		self::initOptions(this).show();

		lx.on('click', [this, self::outclick]);
	}

	/**
	 * Инициализируем таблицу данными выделенного дропбокса
	 * */
	static initOptions(elem) {
		var options = this.getOptions();

		options.width( elem.width('px')+'px' );
		options.resetContent(elem.data, true);
		options.cells()
			.call('align', lx.CENTER, lx.MIDDLE)
			.call('click', this.choose)
			.call('addClass', 'lx-Dropbox-cell');

		options.satelliteTo(elem);

		return options;
	}

	/**
	 * Находим или создаем верстку для таблицы опций
	 * */
	static getOptions() {
		if (this.options) return this.options;
		var options = this.opened.getModule().get('dropboxOptions');
		if (options) {
			this.options = options;
			return options;
		}
		this.options = this.createOptions();
		return this.options;
	}

	/**
	 * Создаем верстку для таблицы опций
	 * */
	static createOptions() {
		var tab = new Table({
			key: 'dropboxOptions',
			height: 0,
			cols: 1
		}).style('z-index', 1001).hide();
		return tab;
	}
}

lx.Dropbox.opened = null;
lx.Dropbox.options = null;
