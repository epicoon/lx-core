#lx:use lx.BitMap;

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

class GridPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	#lx:const
		TYPE_SIMPLE = 1,
		TYPE_PROPORTIONAL = 2,
		TYPE_STREAM = 3,
		TYPE_ADAPTIVE = 4,
		DEAULT_COLUMNS_AMOUNT = 12,

		COLUMN_MIN_WIDTH = '40px',
		ROW_MIN_HEIGHT = '40px';

	init(config={}) {
		//TODO direction?
		this.type = config.type || self::TYPE_SIMPLE;
		if (this.type !== self::TYPE_ADAPTIVE)
			this.cols = config.cols || self::DEAULT_COLUMNS_AMOUNT;
		if (this.type == self::TYPE_SIMPLE || this.type == self::TYPE_PROPORTIONAL)
			this.map = new lx.BitMap(this.cols);

		if (config.minHeight !== undefined) this.minHeight = config.minHeight;
		if (config.minWidth !== undefined) this.minWidth = config.minWidth;
		if (config.maxHeight !== undefined) this.maxHeight = config.maxHeight;
		if (config.maxWidth !== undefined) this.maxWidth = config.maxWidth;
		if (config.height !== undefined) {
			this.minHeight = config.height;
			this.maxHeight = config.height;
		}
		if (config.width) {
			this.minWidth = config.width;
			this.maxWidth = config.width;
		}

		this.owner.addClass('lxps-grid-v');
		if (this.type == self::TYPE_ADAPTIVE)
			this.owner.style(
				'grid-template-columns',
				'repeat(auto-fill,minmax('
					+ lx.getFirstDefined(this.minWidth, self::COLUMN_MIN_WIDTH)
					+ ',1fr))'
			);
		else
			this.owner.style('grid-template-columns', 'repeat(' + this.cols + ',1fr)');
		if (this.type != self::TYPE_PROPORTIONAL) {
			this.owner.height('auto');
		}
		this.setIndents(config);
	}

	setCols(cols) {
		if (this.type == self::TYPE_ADAPTIVE || this.cols === cols) return;
		this.cols = cols;
		if (this.map) this.map.setX(cols);
		this.owner.style('grid-template-columns', 'repeat(' + this.cols + ',1fr)');
	}
	
	#lx:server packProcess() {
		var str = ';t:' + this.type;
		if (this.cols !== undefined)
			str += ';c:' + this.cols;
		if (this.minHeight)
			str += ';mh:' + this.minHeight;
		if (this.minWidth)
			str += ';mw:' + this.minWidth;
		if (this.maxHeight)
			str += ';mxh:' + this.maxHeight;
		if (this.maxWidth)
			str += ';mxw:' + this.maxWidth;
		if (this.map !== undefined)
			str += ';m:' + this.map.toString();
		return str;
	}

	#lx:client unpackProcess(config) {
		this.type = +config.t;
		if (config.c !== undefined) this.cols = +config.c;
		if (config.mh) this.minHeight = config.mh;
		if (config.mw) this.minWidth = config.mw;
		if (config.mxh) this.maxHeight = config.mxh;
		if (config.mxw) this.maxWidth = config.mxw;
		if (config.m !== undefined)
			this.map = config.m == ''
				? new lx.BitMap(this.cols)
				: lx.BitMap.createFromString(config.m);
	}

	reset() {
		if (this.map) this.map.fullReset();
	}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		elem.style('position', 'relative');
		elem.style('min-height', lx.getFirstDefined(config.minHeight, this.minHeight, self::ROW_MIN_HEIGHT));
		elem.style('min-width', lx.getFirstDefined(config.minWidth, this.minWidth, self::COLUMN_MIN_WIDTH));
		var maxHeight = lx.getFirstDefined(config.maxHeight, this.maxHeight),
			maxWidth = lx.getFirstDefined(config.maxWidth, this.maxWidth);
		if (maxHeight) elem.style('max-height', maxHeight);
		if (maxWidth) elem.style('max-width', maxWidth);

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
	if (self.type == lx.GridPositioningStrategy.TYPE_SIMPLE || self.type == lx.GridPositioningStrategy.TYPE_PROPORTIONAL) {
		var geom = self.geomFromConfig(config);
		if (!geom.w) geom.w = 1;
		if (!geom.h) geom.h = 1;
		var params = __prepareInGridParams(self, geom),
			rows = self.map.y;

		self.owner.style('grid-template-rows', 'repeat(' + rows + ',1fr)');
		elem.style('grid-area', params.join('/'));
	}
}

function __prepareInGridParams(self, geom) {
	if (geom.l !== undefined && geom.t === undefined) geom.t = 0;
	if (geom.t !== undefined && geom.l === undefined) geom.l = 0;
	if (geom.w > self.map.x) geom.w = self.map.x;
	var needSetBit = geom.l === undefined;
	if (!needSetBit) {
		if (geom.t+geom.h > self.map.y) self.map.setY(geom.t+geom.h);
		return [
			geom.t+1,
			geom.l+1,
			geom.t+geom.h+1,
			geom.l+geom.w+1
		];
	}

	var crds = self.map.findSpace(geom.w, geom.h);;
	while (crds === false) {
		self.map.addY();
		crds = self.map.findSpace(geom.w, geom.h);
	}

	var l = crds[0], t = crds[1];
	self.map.setSpace([l, t, geom.w, geom.h]);
	return [
		t+1,
		l+1,
		t+geom.h+1,
		l+geom.w+1
	];
}
