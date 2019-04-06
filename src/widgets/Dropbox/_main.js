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

	postUnpack() {
		if (this.data.isObject) this.data = new lx.Dict(this.data);
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

	option(num) {
		return this.children.options.cell(num, 0);
	}

	selectedText() {
		if (this.val === null || this.val === '') return '';

		return this.data[this.val];
	}

	value(val) {
		if (val === undefined) return this.val;

		this.val = val;
		this.text(this.selectedText());
		return this;
	}

	close() {
		if (!lx.Dropbox.opened) return;
		lx.Dropbox.options.hide();
		lx.off('click', [this, lx.Dropbox.outclick]);
		lx.Dropbox.opened = null;
	}

	static choose(event) {
		var dropbox = lx.Dropbox.opened,
			oldVal = dropbox.value(),
			num = this.rowIndex();
		dropbox.select(num);
		dropbox.trigger('change', event, oldVal, dropbox.value());
	}

	static outclick() {
		if (lx.Dropbox.opened) lx.Dropbox.opened.close();
	}

	static open(event) {
		event.stopPropagation();

		lx.Dropbox.opened = this;
		lx.Dropbox.initOptions(this).show();

		lx.on('click', [this, lx.Dropbox.outclick]);
	}

	/**
	 * Инициализируем таблицу данными выделенного дропбокса
	 * */
	static initOptions(elem) {
		var options = this.getOptions();

		options.width( elem.width('px')+'px' );

		var data = [];
		for (var key in elem.data) {
			data.push(elem.data[key]);
		}

		options.resetContent(data, true);

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
		if (lx.Dropbox.options) return lx.Dropbox.options;
		var options = lx.Dropbox.opened.getModule().get('dropboxOptions');
		if (options) {
			lx.Dropbox.options = options;
			return options;
		}
		lx.Dropbox.options = lx.Dropbox.createOptions();
		return lx.Dropbox.options;
	}

	/**
	 * Создаем верстку для таблицы опций
	 * */
	static createOptions() {
		var tab = new Table({
			parent: lx.body,
			key: 'dropboxOptions',
			height: 0,
			cols: 1
		}).style('z-index', 1001).hide();
		return tab;
	}
}

lx.Dropbox.opened = null;
lx.Dropbox.options = null;
