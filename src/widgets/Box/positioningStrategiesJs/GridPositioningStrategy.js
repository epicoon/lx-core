/*
display: grid;

// Отступы между элементами грида:
grid-row-gap: 10px;
grid-column-gap: 10px;
// Одновременно строки и столбцы
grid-gap: 10px;

// Карта грида для вложенных элементов
grid-template-areas:
“nav header  header”
“nav article ads”
“nav footer  ads”;
// Для внутреннего элемента надо накинуть, н-р для nav:
grid-area: nav;
// В карте грида можно задавать пустые ячейки точкой или многоточием (. или ...)

// Следующий код выдает размеры явным (определенным в area, или просто определяемым по числу заданных размеров) строкам и колонкам:
grid-template-rows: 60px 1fr 60px;
grid-template-columns: 20% 1fr 15%;

// Адаптивный грид (еще есть auto-fit, но не понравился)
grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
// Еще адаптивность - после основного определения, переопределить шаблон для экранов определенного размера
@media all and (max-width: 575px) {

// Определить в каком направлении будет расширяться неявный грид:
grid-auto-flow: column;
// По умолчанию row
// Есть значение dense - перетасовывает содержимое, чтобы оно занимало минимум места

// Размеры для неявных строк и колонок:
grid-auto-rows: 60px;
grid-auto-columns: 20%;

// Локализация внутреннего элемента в сетке
grid-row-start: 3;
grid-column-start: 2;
grid-row-end: 4;
grid-column-end: 4;



// Далее стоит покурить
https://medium.com/@stasonmars/%D0%B2%D0%B5%CC%88%D1%80%D1%81%D1%82%D0%BA%D0%B0-%D0%BD%D0%B0-grid-%D0%B2-css-%D0%BF%D0%BE%D0%BB%D0%BD%D0%BE%D0%B5-%D1%80%D1%83%D0%BA%D0%BE%D0%B2%D0%BE%D0%B4%D1%81%D1%82%D0%B2%D0%BE-%D0%B8-%D1%81%D0%BF%D1%80%D0%B0%D0%B2%D0%BE%D1%87%D0%BD%D0%B8%D0%BA-220508316f8b
// Для именованных групп
* Формы с авто-размещением
// Для выравниваний
* CSS Grid выравнивание

// Больше подробностей и примеров, меньше воды
https://css-tricks.com/snippets/css/complete-guide-grid/
*/

#lx:private;

class GridPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	#lx:const
		TYPE_SIMPLE = 1,
		TYPE_PROPORTIONAL = 2,
		DEAULT_COLUMNS_AMOUNT = 12,

		COLUMN_MIN_WIDTH = '40px',
		ROW_MIN_HEIGHT = '40px';

	init(config={}) {
		//TODO direction?
		this.type = config.type || self::TYPE_SIMPLE;
		this.cols = config.cols || self::DEAULT_COLUMNS_AMOUNT;
		this.map = new lx.BitMap(this.cols);

		if (config.minHeight !== undefined) this.minHeight = config.minHeight;

		this.owner.addClass('lxps-grid-v');
		this.owner.style('grid-template-columns', 'repeat(' + this.cols + ',1fr)');
		if (this.type == self::TYPE_SIMPLE) {
			this.owner.height('auto');
		}
		this.setIndents(config);
	}
	
	#lx:server pack() {
		var str = super.pack();
		str += ';c:' + this.cols + ';t:' + this.type;
		if (this.minHeight)
			str += ';mh:' + this.minHeight;
		str += ';m:' + this.map.toString();
		return str;
	}

	#lx:client unpackProcess(config) {
		this.cols = +config.c;
		this.type = +config.t;
		if (config.mh) this.minHeight = config.mh;
		this.map = config.m == ''
			? new lx.BitMap(this.cols)
			: lx.BitMap.createFromString(config.m);
	}

	reset() {
		this.map.fullReset();
	}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		elem.style('position', 'relative');
		elem.style(
			'min-height',
			config.minHeight !== undefined
				? config.minHeight
				: (this.minHeight === undefined ? self::ROW_MIN_HEIGHT : this.minHeight)
		);
		elem.style(
			'min-width',
			config.minWidth !== undefined
				? config.minWidth
				: self::COLUMN_MIN_WIDTH
		);

		__allocate(this, elem, config);
	}

	setIndents(config) {
		super.setIndents(config);
		//TODO false - рефакторинговый костыль. Использование этого флага в перспективе должно быть упразднено
		var indents = this.getIndents(false);

		//TODO - абзац повторяется в разных стратегиях
		if (indents.paddingTop) this.owner.style('padding-top', indents.paddingTop);
		if (indents.paddingBottom) this.owner.style('padding-bottom', indents.paddingBottom);
		if (indents.paddingLeft) this.owner.style('padding-left', indents.paddingLeft);
		if (indents.paddingRight) this.owner.style('padding-right', indents.paddingRight);

		if (indents.stepY) this.owner.style('grid-row-gap', indents.stepY);
		if (indents.stepX) this.owner.style('grid-column-gap', indents.stepX);
	}
}

/******************************************************************************************************************************
 * PRIVATE
 *****************************************************************************************************************************/
function __allocate(self, elem, config) {
	var geom = self.geomFromConfig(config);
	if (!geom.w) geom.w = 1;
	if (!geom.h) geom.h = 1;
	var params = __getInGridParams(self, geom);
	if (params.needSetBit)
		self.map.setSpace(params.needSetBit);
	self.owner.style('grid-template-rows', 'repeat(' + self.map.y + ',1fr)');
	elem.style('grid-area', params.params.join('/'));
}

function __getInGridParams(self, geom) {
	if (geom.l !== undefined && geom.t === undefined) geom.t = 0;
	if (geom.t !== undefined && geom.l === undefined) geom.l = 0;
	var needSetBit = geom.l === undefined;
	if (!needSetBit) {
		if (geom.t+geom.h > self.map.y) self.map.setY(geom.t+geom.h);
		return {
			needSetBit,
			params: [
				geom.t+1,
				geom.l+1,
				geom.t+geom.h+1,
				geom.l+geom.w+1
			]
		};
	}

	var crds = self.map.findSpace(geom.w, geom.h);;
	while (crds === false) {
		self.map.addY();
		crds = self.map.findSpace(geom.w, geom.h);
	}

	var l = crds[0], t = crds[1];
	return {
		needSetBit: [l, t, geom.w, geom.h],
		params: [
			t+1,
			l+1,
			t+geom.h+1,
			l+geom.w+1
		]
	};
}
