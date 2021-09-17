#lx:private;

class StreamPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	#lx:const
		TYPE_SIMPLE = 1,
		TYPE_PROPORTIONAL = 2,

		ROW_DEFAULT_HEIGHT = '40px',
		COLUMN_DEFAULT_WIDTH = '40px',
		ROW_MIN_HEIGHT = '40px',
		COLUMN_MIN_WIDTH = '40px';

	init(config={}) {
		this.type = config.type || self::TYPE_SIMPLE;
		this.sequense = Sequense.create(this);

		if (config.direction === undefined)
			config.direction = (this.owner && this.owner.parent && this.owner.parent.getStreamDirection() === lx.VERTICAL)
				? lx.HORIZONTAL
				: lx.VERTICAL;
		this.direction = config.direction;

		if (config.rowDefaultHeight !== undefined) this.rowDefaultHeight = config.rowDefaultHeight;
		if (config.columnDefaultWidth !== undefined) this.columnDefaultWidth = config.columnDefaultWidth;

		this.owner.addClass(this.direction == lx.VERTICAL ? 'lxps-grid-v' : 'lxps-grid-h');
		if (this.type == self::TYPE_SIMPLE) {
			if (this.direction == lx.VERTICAL) {
				if (this.owner.top() !== null && this.owner.bottom() !== null) this.owner.bottom(null);
				this.owner.height('auto');
			} else {
				if (this.owner.left() !== null && this.owner.right() !== null) this.owner.right(null);
				this.owner.width('auto');
			}
		}

		if (config.minWidth !== undefined) this.minWidth  = config.minWidth;
		if (config.minHeight !== undefined) this.minHeight = config.minHeight;

		this.setIndents(config);
	}

	#lx:server packProcess() {
		var str = ';t:' + this.type + ';d:' + this.direction;
		if (this.rowDefaultHeight)
			str += ';rdh:' + this.rowDefaultHeight;
		if (this.columnDefaultWidth)
			str += ';rdc:' + this.columnDefaultWidth;
		if (this.minWidth)
			str += ';mw:' + this.minWidth;
		if (this.minHeight)
			str += ';mh:' + this.minHeight;
		return str;
	}

	#lx:client unpackProcess(config) {
		this.type = +config.t || self::TYPE_SIMPLE;
		this.direction = +config.d;
		if (config.rdh) this.rowDefaultHeight = config.rdh;
		if (config.rdc) this.columnDefaultWidth = config.rdc;
		if (config.mw !== undefined) this.minWidth = config.mw;
		if (config.mh !== undefined) this.minHeight = config.mh;
		this.sequense = Sequense.create(this);
	}

	reset() {
		if (this.direction == lx.VERTICAL) this.owner.style('grid-template-rows', null)
		else this.owner.style('grid-template-columns', null);
	}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		elem.style('position', 'relative');

		if (this.direction == lx.VERTICAL) {
			var minHeight = config.minHeight !== undefined
				? config.minHeight
				: (this.minHeight !== undefined ? this.minHeight : self::ROW_MIN_HEIGHT);
			elem.style('min-height', minHeight);
		} else {
			var minWidth = config.minWidth !== undefined
				? config.minWidth
				: (this.minWidth !== undefined ? this.minWidth : self::COLUMN_MIN_WIDTH);
			elem.style('min-width', minWidth);
		}

		var geom = this.geomFromConfig(config, elem);
		if (geom.h) this.tryReposition(elem, lx.HEIGHT, geom.h);
		else if (geom.w) this.tryReposition(elem, lx.WIDTH, geom.w);
	}

	onDel() {
		var styleParam = this.direction == lx.VERTICAL
			? 'grid-template-rows'
			: 'grid-template-columns',
			arr = [];
		this.owner.getChildren().each((c)=>arr.push(c.streamSize));
		this.owner.style(styleParam, arr.join(' '));
	}

	tryReposition(elem, param, val) {
		if (this.direction == lx.VERTICAL && param != lx.HEIGHT) return false;
		if (this.direction == lx.HORIZONTAL && param != lx.WIDTH) return false;
		this.sequense.setParam(elem, lx.Geom.geomName(param), val);
		return true;
	}

	setIndents(config) {
		super.setIndents(config);
		//TODO false - рефакторинговый костыль. Использование этого флага в перспективе должно быть упразднено
		var indents = this.getIndents(false);

		//TODO - будет актуально и для грида
		if (indents.paddingTop) this.owner.style('padding-top', indents.paddingTop);
		if (indents.paddingBottom) this.owner.style('padding-bottom', indents.paddingBottom);
		if (indents.paddingLeft) this.owner.style('padding-left', indents.paddingLeft);
		if (indents.paddingRight) this.owner.style('padding-right', indents.paddingRight);

		if (this.direction == lx.VERTICAL && indents.stepY) this.owner.style('grid-row-gap', indents.stepY);
		else if (this.direction == lx.HORIZONTAL && indents.stepX) this.owner.style('grid-column-gap', indents.stepX);
	}

	geomFromConfig(config, elem) {
		return this.sequense.getGeom(super.geomFromConfig(config), elem);
	}
}


/******************************************************************************************************************************
 * PRIVATE
 *****************************************************************************************************************************/

class Sequense {
	constructor(owner) {
		this.owner = owner;
	}

	static create(owner) {
		switch (owner.type) {
			case lx.StreamPositioningStrategy.TYPE_SIMPLE: return new SequenseSimple(owner);
			case lx.StreamPositioningStrategy.TYPE_PROPORTIONAL: return new SequenseProportional(owner);
		}
	}

	getGeom(config) {}
	setParam(elem, param, val) {}
}

class SequenseSimple extends Sequense {
	getGeom(geom, elem) {
		if (this.owner.direction == lx.VERTICAL && geom.h === undefined) {
			if (this.owner.rowDefaultHeight !== null)
				geom.h = this.owner.rowDefaultHeight === undefined
					? lx.StreamPositioningStrategy.ROW_DEFAULT_HEIGHT
					: this.owner.rowDefaultHeight;
		} else if (this.owner.direction == lx.HORIZONTAL && geom.w === undefined) {
			if (this.owner.columnDefaultWidth !== null)
				geom.w = this.owner.columnDefaultWidth === undefined
					? lx.StreamPositioningStrategy.COLUMN_DEFAULT_WIDTH
					: this.owner.columnDefaultWidth;
		}
		return geom;
	}

	setParam(elem, param, val) {
		elem.style(param, val);
	}
}

class SequenseProportional extends Sequense {
	getGeom(geom, elem) {
		if (this.owner.direction == lx.VERTICAL && geom.h === undefined)
			geom.h = 1;
		else if (this.owner.direction == lx.HORIZONTAL && geom.w === undefined)
			geom.w = 1;
		return geom;
	}

	setParam(elem, param, val) {
		if (val.isNumber) val = val + 'fr';
		var needBuild = ((elem.streamSize !== undefined) || elem.nextSibling());
		elem.streamSize = val;
		var styleParam = this.owner.direction == lx.VERTICAL
			? 'grid-template-rows'
			: 'grid-template-columns';
		if (needBuild) {
			var arr = [];
			this.owner.owner.getChildren().each((c)=>arr.push(c.streamSize));
			this.owner.owner.style(styleParam, arr.join(' '));
		} else {
			var tpl = this.owner.owner.style(styleParam);
			tpl = tpl ? tpl + ' ' : '';
			this.owner.owner.style(
				styleParam,
				tpl + elem.streamSize
			); 
		}
	}
}
