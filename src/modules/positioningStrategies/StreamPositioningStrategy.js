#lx:module lx.StreamPositioningStrategy;

#lx:use lx.IndentData;
#lx:use lx.PositioningStrategy;

class StreamPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	#lx:const
		TYPE_SIMPLE = 1,
		TYPE_PROPORTIONAL = 2,

		COLUMN_DEFAULT_WIDTH = '40px',
		ROW_DEFAULT_HEIGHT = '40px',
		COLUMN_MIN_WIDTH = '40px',
		ROW_MIN_HEIGHT = '40px';

	/**
	 * @param [config = {}] {Object: {
	 *     {Number&Enum(
	 *         lx.StreamPositioningStrategy.TYPE_SIMPLE,
	 *         lx.StreamPositioningStrategy.TYPE_PROPORTIONAL
	 *     )} [type = lx.StreamPositioningStrategy.TYPE_SIMPLE],
	 *     {Number&Enum(
	 *         lx.HORIZONTAL,
	 *         lx.VERTICAL
	 *     )} [direction = lx.VERTICAL],
	 *     {String} [width = lx.StreamPositioningStrategy.COLUMN_DEFAULT_WIDTH],
	 *     {String} [height = lx.StreamPositioningStrategy.ROW_DEFAULT_HEIGHT],
	 *     {String} [minWidth = lx.StreamPositioningStrategy.COLUMN_MIN_WIDTH],
	 *     {String} [minHeight = lx.StreamPositioningStrategy.ROW_MIN_HEIGHT],
	 *     {String} [maxWidth]
	 *     {String} [maxHeight]
	 *     #merge(lx.IndentData::constructor::config)
	 * }}
	 */
	init(config={}) {
		this.type = config.type || self::TYPE_SIMPLE;
		this.sequense = Sequense.create(this);

		if (config.direction === undefined)
			config.direction = (this.owner && this.owner.parent && this.owner.parent.getStreamDirection() === lx.VERTICAL)
				? lx.HORIZONTAL
				: lx.VERTICAL;
		this.direction = config.direction;

		if (config.height !== undefined) this.rowDefaultHeight = config.height;
		if (config.width !== undefined) this.columnDefaultWidth = config.width;

		this.owner.addClass(this.direction == lx.VERTICAL ? 'lxps-grid-v' : 'lxps-grid-h');
		if (this.type == self::TYPE_SIMPLE) {
			if (this.direction == lx.VERTICAL) {
				if (this.owner.top() !== null && this.owner.bottom() !== null) this.owner.bottom(null);
				this.owner.height('auto');
			} else {
				if (this.owner.left() !== null && this.owner.right() !== null) this.owner.right(null);
				this.owner.width('auto');
				this.owner.style('display', 'inline-grid');
			}
		}

		this.minWidth = lx.getFirstDefined(config.minWidth, null);
		this.minHeight = lx.getFirstDefined(config.minHeight, null);
		this.maxWidth = lx.getFirstDefined(config.maxWidth, null);
		this.maxHeight = lx.getFirstDefined(config.maxHeight, null);
		this.setIndents(config);
	}

	#lx:server packProcess() {
		var str = ';t:' + this.type + ';d:' + this.direction;
		if (this.rowDefaultHeight)
			str += ';rdh:' + this.rowDefaultHeight;
		if (this.columnDefaultWidth)
			str += ';rdc:' + this.columnDefaultWidth;
		if (this.minWidth !== null)
			str += ';mw:' + this.minWidth;
		if (this.minHeight !== null)
			str += ';mh:' + this.minHeight;
		if (this.maxWidth !== null)
			str += ';maw:' + this.maxWidth;
		if (this.maxHeight !== null)
			str += ';mah:' + this.maxHeight;
		return str;
	}

	#lx:client unpackProcess(config) {
		this.type = +config.t || self::TYPE_SIMPLE;
		this.direction = +config.d;
		if (config.rdh) this.rowDefaultHeight = config.rdh;
		if (config.rdc) this.columnDefaultWidth = config.rdc;
		if (config.mw !== undefined) this.minWidth = config.mw;
		if (config.mh !== undefined) this.minHeight = config.mh;
		if (config.maw !== undefined) this.maxWidth = config.maw;
		if (config.mah !== undefined) this.maxHeight = config.mah;
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
				: (this.minHeight !== null ? this.minHeight : self::ROW_MIN_HEIGHT);
			elem.style('min-height', minHeight);
			if (this.maxHeight !== null)
				elem.style('max-height', this.maxHeight);
		} else {
			var minWidth = config.minWidth !== undefined
				? config.minWidth
				: (this.minWidth !== null ? this.minWidth : self::COLUMN_MIN_WIDTH);
			elem.style('min-width', minWidth);
			if (this.maxWidth !== null)
				elem.style('max-width', this.maxWidth);
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
		this.owner.getChildren().forEach(c=>arr.push(c.streamSize));
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
		if (lx.isNumber(val)) val = val + 'fr';
		var needBuild = ((elem.streamSize !== undefined) || elem.nextSibling());
		elem.streamSize = val;
		var styleParam = this.owner.direction == lx.VERTICAL
			? 'grid-template-rows'
			: 'grid-template-columns';
		if (needBuild) {
			var arr = [];
			this.owner.owner.getChildren().forEach(c=>arr.push(c.streamSize));
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
