#private;

#use lx.PositioningStrategy as PositioningStrategy;

/*
формирует сетку с заданным количеством колонок, высота строки может быть задана, или будет определена автоматически исходя из размера коробки				
количество строк может быть не задано, тогда строки формируются по мере наполнения контейнера. Либо фиксированное количество строк, тогда вместимость ограничена				
параметры располагаемым элементам задаются в условных единицах (кол-во колонок, кол-во строк)				
старается размещать новые элементы слева от уже расположенных, и ниже последней занятой строки				
	фиксированное число строк	SIZE_BEHAVIOR_PROPORTIONAL_CONST
	адаптируемая высота строк	SIZE_BEHAVIOR_PROPORTIONAL
	управляющая высота строк	SIZE_BEHAVIOR_BY_CONTENT
*/
class GridPositioningStrategy extends PositioningStrategy #in lx {
	constructor(owner, config) {
		super(owner);
		this.innerFormat = PositioningStrategy.FORMAT_PX;
		this.defaultFormat = PositioningStrategy.FORMAT_PX;

		if (config) this.init(config);
	}

	init(config={}) {
		this.rowHeight = config.rowHeight || self::ROW_HEIGHT;
		this.cols = config.cols || self::COLS;
		this.sizeBehavior = (config.sizeBehavior !== undefined) ? config.sizeBehavior : self::HEIGHT_BEHAVIOR;

		if (this.sizeBehavior == self::SIZE_BEHAVIOR_PROPORTIONAL_CONST) {
			this.rows = config.rows || 1;
			this.map = new Array(this.rows);
			this.map.each((a, i)=> this.map[i]=0);
		} else this.map = [0];

		if (config.indent === undefined) {
			if (config.padding === undefined) config.padding = self::PADDING;
			if (config.step === undefined) config.step = self::STEP;			
		}

		this.setIndents(config);
	}

	unpackProcess(config) {
		this.cols = +config.c;
		this.sizeBehavior = +config.hb;
		this.map = config.m.split(',');

		this.rowHeight = config.rh || self::ROW_HEIGHT;
		if (config.r) this.rows = +config.r;
	}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		var gc = GridCalculator.create(this);
		gc.toGrid(elem, config);
		gc.relocateElem(elem);
	}

	/**
	 * Актуализация позиций элементов в контейнере
	 * info = {
	 *	from: Rect  // если указан - с него начать актуализацию
	 *	changed: Rect  // если указан - актуализация была вызвана в связи с изменением этих элементов
	 *	deleted: Array  // если указан - актуализация была вызвана в связи с удалением этих элементов
	 * }
	 * */
	actualizeProcess(info) {
		var gc = GridCalculator.create(this);
		gc.relocateElems();
	}

	/**
	 * Сброс данных стратегии при очистке контейнера
	 * */
	reset() {
		this.owner.getChildren().each((a)=> { delete a.inGrid; });
		this.map = [0];
	}

	/**
	 * Запрос на изменение позиционного параметра для конкретного элемента
	 * Должен вернуть булево значения - true и поменять параметр, либо false и не менять параметр
	 * */
	tryReposition(elem, param, val) {
		//todo RIGHT BOTTOM не поддерживается!!!
		// if (param == lx.RIGHT)

		elem.inGrid[param] = val;
		GridCalculator.create(this).relocateElem(elem);
	}
}

//=============================================================================================================================

//=============================================================================================================================
class GridCalculator {
	constructor() {
		this.grid = null;
		this.indents = [];
		this.mapX = [];
	}

	static create(grid) {
		var obj;
		switch (grid.sizeBehavior) {
			case lx.GridPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL_CONST: obj = new ConstGridCalculator(); break;
			case lx.GridPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL: obj = new RowsGridCalculator(); break;
			case lx.GridPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT: obj = new BoxGridCalculator(); break;
		}
		obj.grid = grid;
		obj.indents = grid.getIndents();
		obj.mapX = obj.gridMap();
		return obj;
	}

	relocateElem(elem) {}
	relocateElems() {}

	toGrid(elem, config) {
		var geom = this.grid.geomFromConfig(config);
		geom = this.normalizeGeomH(geom);
		geom = this.normalizeGeomV(geom, elem);
		elem.inGrid = [];
		elem.inGrid[lx.LEFT] = geom.l;
		elem.inGrid[lx.TOP] = geom.t;
		elem.inGrid[lx.WIDTH] = geom.w;
		elem.inGrid[lx.HEIGHT] = geom.h;
	}

	normalizeGeomV(geom, elem) {
		return geom;
	}

	normalizeGeomH(geom) {
		var grid = this.grid;

		// для ситуации, когда задан right
		var definedCount = +(geom.w!==undefined) + +(geom.l!==undefined) + +(geom.r!==undefined);
		if (definedCount == 3) geom.r = undefined;
		if (geom.r !== undefined) {
			if (definedCount == 1) {
				geom.l = 0;
				geom.w = grid.cols - geom.r;
			} else {
				if (geom.l !== undefined) geom.w = grid.cols - geom.l - geom.r;
				else geom.l = grid.cols - geom.w - geom.r;
			}
			geom.r = undefined;
		}

		// по ширине не вылазим
		if (geom.w === undefined || geom.w > grid.cols) geom.w = grid.cols;
		return geom;
	}

	gridMap(axis=lx.HORIZONTAL) {
		var grid = this.grid,
			count, size, ind;
		if (axis == lx.HORIZONTAL) {
			count = grid.cols;
			size = grid.owner.width('px');
			ind = 1;
		} else {
			count = grid.rows || grid.map.len;
			size = grid.owner.height('px');
			ind = 0;
		}

		var indents = this.indents,
			padding = indents.padding[ind],
			step = indents.step[ind],
			wi = size - padding[0] - padding[1] - (count - 1) * step,
			col = Math.floor(wi / count),
			residue0 = wi % count,
			residue1 = (residue0 > count*0.5) ? residue0 - Math.floor(count*0.5) : 0,
			res = [padding[0]];

		for (var i=0; i<count; i++) {
			var val = col;
			if (i%2) {
				if (residue1 > 0) { residue1--; val++; }
			} else {
				if (residue0 > 0) { residue0--; val++; }
			}
			res.push(val);
			if (i == count-1) res.push(padding[1]);
			else res.push(step);
		}
		return res;
	}

	setParams(elem, l, t, w, h) {
		var grid = this.grid;
		grid.setParam(elem, lx.LEFT, l, true);
		grid.setParam(elem, lx.TOP, t, true);
		grid.setParam(elem, lx.WIDTH, w, true);
		grid.setParam(elem, lx.HEIGHT, h, true);
		elem.trigger('resize');
	}

	getParamsH(elem) {
		var grid = this.grid,
			geom = elem.inGrid,
			mapX = this.mapX,
			lim = +geom[lx.LEFT] * 2 + 1,
			lim2 = (+geom[lx.WIDTH] - 1) * 2 + 1,
			left = 0,
			width = 0;
		for (var i=0; i<lim; i++) left += +mapX[i];
		for (var i=lim; i<lim+lim2; i++) width += +mapX[i];

		for (var i=geom[lx.TOP], l=+geom[lx.TOP] + +geom[lx.HEIGHT]; i<l; i++)
			grid.map[i] = Math.max(+geom[lx.LEFT] + +geom[lx.WIDTH], grid.map[i]);

		return [left, width];
	}
}
//=============================================================================================================================

//=============================================================================================================================
class ConstGridCalculator extends GridCalculator {
	relocateElem(elem) {
		this.relocateElemProcess(elem);
	}

	relocateElems() {
		this.grid.owner.getChildren().each((a)=> this.relocateElemProcess(a));
	}

	relocateElemProcess(elem) {
		var hor = this.getParamsH(elem),
			vert = this.getParamsV(elem);
		this.setParams(elem, hor[0], vert[0], hor[1], vert[1]);
	}

	getParamsV(elem) {
		var grid = this.grid,
			geom = elem.inGrid,
			mapY = this.mapY(),
			lim = +geom[lx.TOP] * 2 + 1,
			lim2 = (+geom[lx.HEIGHT] - 1) * 2 + 1,
			top = 0,
			height = 0;
		for (var i=0; i<lim; i++) top += +mapY[i];
		for (var i=lim; i<lim+lim2; i++) height += +mapY[i];
		return [top, height];
	}

	mapY() {
		if (!this._mapY) this._mapY = this.gridMap(lx.VERTICAL);
		return this._mapY;
	}

	/**
	 * Ищем последнюю строку, которую займет предполагаемая геометрия
	 * */
	getLastLine(geom) {
		var row;
		if (geom.t) row = geom.t + geom.h - 1;
		else {
			row = this.findLine(geom);
			if (row === false) {
				// todo ??? тут бы надо исключение - типа не влезло
				row = 1;
				geom.t = 0;
			} else geom.t = row + 1 - geom.h;
		}

		return row;
	}

	normalizeGeomV(geom, elem) {
		var grid = this.grid;

		// по высоте корректируем
		if (geom.h === undefined) geom.h = 1;
		if (geom.h > grid.rows) geom.h = grid.rows;

		// ищем последнюю строку, которую займет элемент
		var row = this.getLastLine(geom);

		// левая позиция может быть указана, иначе - справа от уже существующих элементов
		geom.l = geom.l !== undefined
			? geom.l
			: this.grid.map.maxOnRange(geom.t, row);
		return geom;
	}

	findLine(geom) {
		var grid = this.grid,
			okRows = 0;
		for (var i=0, l=grid.map.len; i<l; i++)
			if (grid.cols - grid.map[i] >= geom.w) {
				okRows++;
				if (okRows == geom.h) return i;
			}
		return false;
	}
}
//=============================================================================================================================

//=============================================================================================================================
class RowsGridCalculator extends ConstGridCalculator {
	constructor() {
		super();
		this.relocateAll = false;
	}

	relocateElem(elem) {
		if (this.relocateAll) this.relocateElems();
		else this.relocateElemProcess(elem);
	}

	/**
	 * Ищем последнюю строку, которую займет предполагаемая геометрия
	 * */
	getLastLine(geom) {
		var grid = this.grid,
			row;
		if (geom.t) row = geom.t + geom.h - 1;
		else {
			row = this.findLine(geom);
			geom.t = row + 1 - geom.h;
		}
		// актуализируем сетку в высоту
		if (row+1 > grid.map.len)
			for (var i=0, l=row+1-grid.map.len; i<l; i++)
				this.addLine();

		return row;
	}

	findLine(geom) {
		var result = super.findLine(geom);
		// если не было найдено место в существующей карте - надо расширить карту
		if (result === false)
			for (var i=0, l=geom.h; i<l; i++) this.addLine();
		// после расширения место найдется точно
		return super.findLine(geom);
	}

	addLine() {
		this.grid.map.push(0);
		this.relocateAll = true;
	}
}
//=============================================================================================================================

//=============================================================================================================================
class BoxGridCalculator extends RowsGridCalculator {
	relocateElem(elem) {
		this.relocateElemProcess(elem);
		this.checkHeight();
	}

	relocateElems() {
		super.relocateElems();
		this.checkHeight();
	}

	getParamsV(elem) {
		var grid = this.grid,
			geom = elem.inGrid,
			indents = this.indents,
			rowHeight = grid.owner.geomPart(grid.rowHeight, 'px', lx.VERTICAL),
			top = geom[lx.TOP] * (rowHeight + indents.step[1]) + indents.padding[1][0],
			height = rowHeight * geom[lx.HEIGHT] + (geom[lx.HEIGHT]-1) * indents.step[1];
		return [top, height];
	}

	checkHeight() {
		var padding = this.indents.padding[1][1],
			grid = this.grid,
			b = 0;
		grid.owner.getChildren().each((a)=>{
			var bottom = a.top('px') + a.height('px');
			if (b < bottom) b = bottom;
		});
		if (grid.owner.height('px') == b + padding) return;

		grid.setParam(grid.owner, lx.HEIGHT, b + padding + 'px', true);
		grid.owner.reportSizeChange(lx.HEIGHT);
	}

	addLine() {
		this.grid.map.push(0);
	}
}
//=============================================================================================================================

lx.GridPositioningStrategy.PADDING = '0px';
lx.GridPositioningStrategy.STEP = '0px';
lx.GridPositioningStrategy.ROW_HEIGHT = '25px';
lx.GridPositioningStrategy.COLS = 12;

//todo - сделать вариант и поставить его по умолчанию: самый простой - размер самого грида не меняется, у строк есть фиксированная высота,
// при необходимости появляется полоса прокрутки
// lx.GridPositioningStrategy.SIZE_BEHAVIOR_SCROLLING = 1;
lx.GridPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT = 2;
lx.GridPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL = 3;
lx.GridPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL_CONST = 4;

lx.GridPositioningStrategy.HEIGHT_BEHAVIOR = lx.GridPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT;
