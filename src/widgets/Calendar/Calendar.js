#lx:use lx.Rect as Rect;
#lx:use lx.Box as Box;
#lx:use lx.Input as Input;
#lx:use lx.Dropbox as Dropbox;
#lx:use lx.Table as Table;

class Calendar extends Input #lx:namespace lx {
	//todo - сделать инициализацию даты + см. тудуху для lx.Date
	postBuild(config) {
		this.date = lx.Date();

		this.value( this.date.format() );
		this.on('mouseup', self::open);
	}
	
	static open() {
		lx.on('mouseup', self::outclick);
		self::active = this;
		self::oldDate = this.date.format();
		self::renew();
		var menu = self::getMenu();

		menu.satelliteTo(this);
	}

	static close(event) {
		if ( this.active.date.format() != this.oldDate )
			this.active.trigger('change', event);
		this.active = null;
		this.getMenu().hide();
		lx.off('mouseup', [this, this.outclick]);
	}

	static outclick(event) {
		if (this.active === null) return;
		event = event || window.event;

		if (event.target.lx === this.active) return;
		if (event.target.lx && (
			event.target.lx.ancestor({hasProperties: {key: 'calendarMenu'}})
			|| (Dropbox.opened && Dropbox.opened.ancestor({hasProperties: {key: 'calendarMenu'}}))
		)) return;
		this.close(event);
	}

	static renew() {
		var date = this.active.date,
			max = date.getMaxDate(),
			row = 1,
			list = this.getMenu(),
			actDate = date.getDate(),
			tab = list.get('table');

		this.active.value( this.active.date.format() );

		list.get('month').value( date.getMonth() );
		list.get('year').value( date.getFullYear() );

		tab.cells(1, 0, 6, 6)
			.call('text', '')
			// .call('removeClass', lx.cssClasses.names['calendarCell'])
			// .call('removeClass', lx.cssClasses.names['calendarActCell'])
			;
		for (var i=1; i<=max; i++) {
			var d = lx.Date( date.getFullYear(), date.getMonth(), i ),
				dow = d.getDay();
			var col = (dow ? dow-1 : 6);
			tab.cell(row, col).text(i);
			// tab.cell(row, col).addClass( lx.cssClasses.names[(i==actDate) ? 'calendarActCell' : 'calendarCell'] );
			if (!dow) row++;
		}
	}

	static setDate(event) {
		var calendar = this.active;

		if ( this.key == 'today' ) {
			calendar.date = lx.Date();
			calendar.value( calendar.date.format() );
			this.close(event);
			return;
		}

		var val = this.text();
		if (val == '') return;

		calendar.date.setDate(val);
		calendar.value( calendar.date.format() );
		this.close(event);
	}

	static getMenu() {
		if (this.menu) {
			this.menu.show();
			return this.menu;
		}

		var calendarMenu = new Box({
			key: 'calendarMenu',
			size: ['200px', '260px'],
			style: { overflow: 'visible' }
		}).style('z-index', 1000);  //todo доберусь до индекса, тут будет косяк
		calendarMenu.begin();

		new Rect({
			key: 'pre',
			geom: [0, 0, '20px', '30px'],
			css: 'lx-Calendar-arroy',
			click: function() {
				lx.Calendar.active.date = lx.Calendar.active.date.shiftMonth(-1);
				lx.Calendar.renew();
			}
		});
		new Rect({
			key: 'post',
			geom: [null, 0, '20px', '30px', 0],
			css: 'lx-Calendar-arroy',
			click: function() {
				lx.Calendar.active.date = lx.Calendar.active.date.shiftMonth(1);
				lx.Calendar.renew();
			}
		}).style('transform', 'scale(-1, 1)');

		new Input({
			key: 'year',
			geom: ['20px', 0, '54px', '30px']
		}).on('blur', function() {
			lx.Calendar.active.date.setFullYear(this.value());
			lx.Calendar.renew();
		});

		var month = new Dropbox({
			key: 'month',
			geom: ['74px', 0, '106px', '30px'],
			options: lx.Date.monthNamesRu()
		}).style('z-index', 1001);
		month.on('change', function() {
			lx.Calendar.active.date.setMonth(this.value());
			lx.Calendar.renew();
		});

		var tab = new Table({
			key: 'table',
			top: '30px',
			bottom: '30px',
			cols: 7,
			rows: 7,
			overflow: 'hidden'
		});
		tab.setContent([ 'П', 'В', 'С', 'Ч', 'П', 'С', 'В' ]);
		tab.row(0).addClass('lx-Calendar-info');
		tab.cells().call('align', lx.CENTER, lx.MIDDLE);
		tab.cells(1, 0, 6, 6).call('click', this.setDate);

		var today = new Box({
			key: 'today',
			height: '30px',
			bottom: 0,
			text: 'Сегодня: ' + lx.Date().format(),
			css: 'lx-Calendar-today'
		}).align(lx.CENTER, lx.MIDDLE);
		today.on('mouseup', this.setDate);

		calendarMenu.end();
		this.menu = calendarMenu;
		return calendarMenu;
	}
}

/*
 * Кэширует таблицу календаря - она нужна в единственном экземпляре, не предусмотрено ситуаций, чтобы было раскрыто несколько календарей одновременно
 * соответственно таблица - синглтон, создается при первом вызове, потом достается из кэша
 * */
lx.Calendar.menu = null;
lx.Calendar.oldDate = null;
lx.Calendar.active = null;
