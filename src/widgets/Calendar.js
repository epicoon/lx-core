#lx:module lx.Calendar;

#lx:use lx.Rect;
#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Dropbox;
#lx:use lx.Table;

#lx:private;
#lx:client {
	/*
	 * Кэширует таблицу календаря - она нужна в единственном экземпляре,
	 * не предусмотрено ситуаций, чтобы было раскрыто несколько календарей одновременно
	 * соответственно таблица - синглтон, создается при первом вызове, потом достается из кэша
	 */
	let __menu = null;
	let __oldDate = null;
	let __active = null;
}

class Calendar extends lx.Input #lx:namespace lx {
	//todo - сделать инициализацию даты + см. тудуху для lx.Date
	build(config) {
		this.date = config.date || lx.Date();
		this.value( this.date.format() );
	}

	#lx:client {
		postBuild(config) {
			this.on('mouseup', _handler_open);
		}

		postUnpack(config) {
			super.postUnpack(config);
			this.date = lx.Date(this.date);
		}
	}
	
	getBasicCss() {
		return {
			main: 'lx-Calendar',
			arroy: 'lx-Calendar-arroy',
			dayOfWeek: 'lx-Calendar-day-of-week',
			today: 'lx-Calendar-today',
			cellToday: 'lx-Calendar-cell-today',
			cellDay: 'lx-Calendar-cell-day',
			menu: 'lx-Calendar-menu'
		};
	}
}

#lx:client {
	function _handler_open() {
		__active = this;
		__oldDate = this.date.format();
		__renew();

		var menu = __getMenu();
		menu.satelliteTo(this);

		lx.on('mouseup', _lxHandler_outclick);
	}

	function _lxHandler_outclick(event) {
		if (__active === null) return;
		event = event || window.event;

		var widget = event.target.__lx;
		if (widget === __active) return;
		if (widget && (
			widget.ancestor({is:__menu})
			|| (lx.Dropbox.getOpened() && lx.Dropbox.getOpened().ancestor({is:__menu}))
		)) return;

		__close(event);
	}

	function __close(event) {
		if ( __active.date.format() != __oldDate )
			__active.trigger('change', event);

		__getMenu().hide();
		__active = null;

		lx.off('mouseup', _lxHandler_outclick);
	}

	function __renew() {
		var date = __active.date,
			max = date.getMaxDate(),
			row = 1,
			list = __getMenu(),
			actDate = date.getDate(),
			tab = list.get('table');

		__active.value( __active.date.format() );

		list->month.value( date.getMonth() );
		list->year.value( date.getFullYear() );

		tab.cells(1, 0, 6, 6)
			.call('text', '')
			.call('removeClass', __active.basicCss.cellDay)
			.call('removeClass', __active.basicCss.cellToday);
		for (var i=1; i<=max; i++) {
			var d = lx.Date( date.getFullYear(), date.getMonth(), i ),
				dow = d.getDay();
			var col = (dow ? dow-1 : 6);
			tab.cell(row, col).text(i);
			if (i == actDate) tab.cell(row, col).addClass(__active.basicCss.cellToday);
			else tab.cell(row, col).addClass(__active.basicCss.cellDay);
			if (!dow) row++;
		}
	}

	function _handler_setDate(event) {
		var calendar = __active;

		if ( this.key == 'today' ) {
			calendar.date = lx.Date();
			calendar.value( calendar.date.format() );
			__close(event);
			return;
		}

		var val = this.text();
		if (val == '') return;

		calendar.date.setDate(val);
		calendar.value( calendar.date.format() );
		__close(event);
	}

	function __getMenu() {
		if (__menu) {
			__menu.show();
			return __menu;
		}

		var calendarMenu = new lx.Box({
			parent: lx.body,
			key: 'calendarMenu',
			geom: true,
			css: __active.basicCss.menu,
			size: ['200px', '236px'],
			style: { overflow: 'visible' }
		}).style('z-index', 1000);  //todo доберусь до индекса, тут будет косяк
		calendarMenu.begin();

		new lx.Rect({
			key: 'pre',
			geom: [0, 0, '20px', '30px'],
			css: __active.basicCss.arroy,
			click: function() {
				__active.date = __active.date.shiftMonth(-1);
				__renew();
			}
		});
		new lx.Rect({
			key: 'post',
			geom: [null, 0, '20px', '30px', 0],
			css: __active.basicCss.arroy,
			click: function() {
				__active.date = __active.date.shiftMonth(1);
				__renew();
			}
		}).style('transform', 'scale(-1, 1)');

		new lx.Input({
			key: 'year',
			geom: ['20px', 0, '54px', '30px']
		}).on('blur', function() {
			__active.date.setFullYear(this.value());
			__renew();
		});

		var month = new lx.Dropbox({
			key: 'month',
			geom: ['74px', 0, '106px', '30px'],
			options: lx.Date.monthNamesRu()
		}).style('z-index', 1001);
		month.on('change', function() {
			__active.date.setMonth(this.value());
			__renew();
		});

		var tab = new lx.Table({
			key: 'table',
			top: '30px',
			cols: 7,
			rows: 7,
			overflow: 'hidden',
			colWidth: '25px'
		});
		tab.setColWidth(0, '29px');
		tab.setContent([ 'П', 'В', 'С', 'Ч', 'П', 'С', 'В' ]);
		tab.row(0).addClass(__active.basicCss.dayOfWeek);
		tab.cells().call('align', lx.CENTER, lx.MIDDLE);
		tab.cells(1, 0, 6, 6).call('click', _handler_setDate);

		var today = new lx.Box({
			key: 'today',
			height: '30px',
			bottom: 0,
			text: 'Сегодня: ' + lx.Date().format(),
			css: __active.basicCss.today
		}).align(lx.CENTER, lx.MIDDLE);
		today.on('mouseup', _handler_setDate);

		calendarMenu.end();
		__menu = calendarMenu;
		return calendarMenu;
	}
}
