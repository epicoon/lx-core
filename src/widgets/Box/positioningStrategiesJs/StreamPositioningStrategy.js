#lx:private;

#lx:use lx.PositioningStrategy as PositioningStrategy;

/**
 * Располагает элементы потоком, игнорирует все параметры кроме размера, соответствующего направлению потока
 * */
class StreamPositioningStrategy extends PositioningStrategy #lx:namespace lx {
	/**
	 * config = direction | {
	 *	direction,
	 *	sizeBehavior,
	 *	defaultSize,
	 *	margin | marginX | marginLeft
	 *					   marginRight
	 *			 marginY | marginTop
	 *			 		   marginBottom
	 *	step | stepX
	 *		   stepY
	 * }
	 * */
	constructor(owner, config) {
		super(owner);
		this.innerFormat = PositioningStrategy.FORMAT_PX;
		this.defaultFormat = PositioningStrategy.FORMAT_PX;

		//todo костыль из-за рекурсивной актуализации сложенных стратегий позиционирования
		this.resizeTriggerOn = true;

		if (config) this.init(config);
	}

	init(config={}) {
		if (config.isNumber) config = { direction: config };

		this.sizeBehavior = config.sizeBehavior || self::SIZE_BEHAVIOR_SCROLLING;

		// если будут добавляться элементы без размера, или с числом - используется это значение (не для пропорционально вычисляемых элементов)
		this.defaultSize = config.defaultSize || self::DEFAULT_SIZE;

		if (config.direction === undefined)
			config.direction = (this.owner.parent && this.owner.parent.streamDirection() === lx.VERTICAL) ? lx.HORIZONTAL : lx.VERTICAL;
		
		this.direction = config.direction;
		if (this.sizeBehavior == self::SIZE_BEHAVIOR_SCROLLING) {
			if (this.direction == lx.VERTICAL) this.owner.style('overflow-y', 'auto');
			else this.owner.style('overflow-x', 'auto');

			if (config.lock) this.setLock(config.lock);
		} else this.owner.style('overflow', 'hidden');

		this.setIndents(config);
		this.actualizeProcess();

		return this;
	}

	unpackProcess(config) {
		this.sizeBehavior = +config.sb || self::SIZE_BEHAVIOR_SCROLLING;
		this.defaultSize = config.ds || self::DEFAULT_SIZE;
		this.direction = +config.d;
		if (config.l) this.setLock(+config.l);
	}

	reactForAutoresize(elem) {
		this.resizeTriggerOn = false;
		this.actualizeProcess({from:elem});
		this.resizeTriggerOn = true;
	}

	getCalc() {
		switch (this.sizeBehavior) {
			case self::SIZE_BEHAVIOR_SCROLLING: return new ScrollingCalc(this);
			case self::SIZE_BEHAVIOR_BY_CONTENT: return new ByContentCalc(this);
			case self::SIZE_BEHAVIOR_PROPORTIONAL: return new ProportionalCalc(this);
		}
	}

	setLock(lock) {
		this.lock = lock;

		this.owner.on('scroll', function() {
			var p = this.positioning(),
				calc = p.getCalc(),
				pre=null,
				current = this.child(0);

			for (var i=0, l=p.lock; i<l; i++) {
				var pos = calc.getPrevLim(pre);
				if (!pre) pos += p.direction == lx.VERTICAL
					? this.scrollPos().y
					: this.scrollPos().x;
				p.setParam(current, calc.geomKeys.posConst, pos);
				current.style('z-index', 1);
				pre = current;
				current = current.nextSibling();
			};
		});
	}

	allocate(el, config={}) {
		var calc = this.getCalc();
		calc.allocate(el, config);
	}

	actualizeProcess(info) {
		var calc = this.getCalc();
		calc.actualize(info);
	}

	tryReposition(elem, param, val) {
		if (this.direction == lx.VERTICAL && param != lx.HEIGHT) return false;
		if (this.direction == lx.HORIZONTAL && param != lx.WIDTH) return false;
		this.getCalc().reposition(elem, param, val);
		return true;
	}

	sizeChanged() {
		var oldSize = this.oldSize,
			currentSize = (this.direction == lx.VERTICAL)
				? this.owner.height('px')
				: this.owner.width('px');

		if (oldSize == currentSize) return false;
		this.oldSize = currentSize;
		return true;
	}

	getGeomKeys() {
		if (this.direction == lx.VERTICAL) return {
			posConst: lx.TOP,
			sizeConst: lx.HEIGHT,
			crossCrdConst: [lx.LEFT, lx.RIGHT],
			sizeName: 'height',
			sizeGeom: 'h'
		};
		return {
			posConst: lx.LEFT,
			sizeConst: lx.WIDTH,
			crossCrdConst: [lx.TOP, lx.BOTTOM],
			sizeName: 'width',
			sizeGeom: 'w'
		};
	}

	getLineIndents(indents) {
		indents = indents || this.getIndents();
		if (this.direction == lx.VERTICAL) return {
			step: indents.stepY,
			padding0: indents.paddingTop,
			padding1: indents.paddingBottom,
			crossPadding0: indents.paddingLeft,
			crossPadding1: indents.paddingRight
		};
		return {
			step: indents.stepX,
			padding0: indents.paddingLeft,
			padding1: indents.paddingRight,
			crossPadding0: indents.paddingTop,
			crossPadding1: indents.paddingBottom,
		};
	}

	getLimit(elem) {
		if (this.direction == lx.VERTICAL) return elem.top('px') + elem.height('px');
		return elem.left('px') + elem.width('px');
	}
}
//=============================================================================================================================

//=============================================================================================================================
class AbstractCalc {
	constructor(stream) {
		this.owner = stream;
		this.indents = stream.getLineIndents();
		this.geomKeys = stream.getGeomKeys();

		var temp = lx.Geom.splitGeomValue(stream.defaultSize);
		this.defaultSize = temp[0];
		this.defaultSizeFormat = temp[1];
	}

	setCrossSizes(elem) {
		var stream = this.owner;
		stream.setParam(elem, this.geomKeys.crossCrdConst[0], this.indents.crossPadding0);
		stream.setParam(elem, this.geomKeys.crossCrdConst[1], this.indents.crossPadding1);
	}

	setAlongSize(elem, val=null) {
		if (val === null) {
			val = [
				this.owner.getSavedParam(elem, this.geomKeys.sizeConst),
				elem[this.geomKeys.sizeName]()
			].lxGetFirstDefined();
			//todo - пиксели могут привести к нежелательным результатам. И вообще допусловие после .lxGetFirstDefined() некрасиво
			if (val === null) val = elem[this.geomKeys.sizeName]('px') + 'px';

			if (val.isNumber) {
				val = val * this.defaultSize + this.defaultSizeFormat;
				//todo - закостылил обход метода `this.owner.setParam` - он перезатирает число значением размера
				// (для потока в основном этот гемор и был сделан, надо рефакторить - числовое знаение, как условное уже вводится тут в потоке, надо см. по остальным стратегиям)
				elem.DOMelem.style[this.geomKeys.sizeName] = val;
				return;
			}
		}

		this.owner.setParam(elem, this.geomKeys.sizeConst, val);
	}

	getPrevLim(pre) {
		if (!pre) return this.indents.padding0;
		return this.owner.getLimit(pre) + this.indents.step;
	}
}
//=============================================================================================================================

//=============================================================================================================================
class ScrollingCalc extends AbstractCalc {
	allocate(el, config) {
		this.setCrossSizes(el);

		var stream = this.owner,
			geom = stream.geomFromConfig(config),
			size = geom[this.geomKeys.sizeGeom] || 1; //stream.defaultSize; //'0px';
		if (size.isNumber) {
			size = size * this.defaultSize + this.defaultSizeFormat;
		}

		this.reposition(el, this.geomKeys.sizeConst, size);
	}

	reposition(el, param, val) {
		this.owner.setSavedParam(el, param, val);
		this.setAlongSize(el);
		this.allocateStream({from: el});
	}

	actualize(info) {
		this.allocateStream(info);
	}

	allocateStream(info={}) {
		var stream = this.owner,
			current = info && info.from
				? info.from
				: stream.owner.child(0);
		if (!current) return false;
		var pre = current.prevSibling(),
			sizeChanged = this.owner.sizeChanged() || info.full,
			counter = 0,
			scrollPos;
		if (stream.lock) {
			scrollPos = lx.VERTICAL
				? stream.owner.scrollPos().y
				: stream.owner.scrollPos().x;
		}

		while (current) {
			var pos = this.getPrevLim(pre);

			if (stream.lock) {
				if (!counter) pos += scrollPos;
				else if (counter == stream.lock) pos -= scrollPos;
				counter++;
			}

			stream.setParam(current, this.geomKeys.posConst, pos);
			if (sizeChanged) this.setAlongSize(current);

			//todo вся моя оптимизация накрылась
			if (stream.resizeTriggerOn) current.trigger('resize');

			pre = current;
			current = current.nextSibling();
		}

		return true;
	}
}
//=============================================================================================================================

//=============================================================================================================================
class ByContentCalc extends ScrollingCalc {
	allocateStream (info) {
		if (!super.allocateStream(info)) return;

		var stream = this.owner;

		// console.log(
		// 	stream.getLimit(stream.owner.lastChild()) + this.indents.padding1 + 'px'
		// );

		stream.setParam(
			stream.owner,
			this.geomKeys.sizeConst,
			stream.getLimit(stream.owner.lastChild()) + this.indents.padding1 + 'px',
			true
		);

		// stream.owner[this.geomKeys.sizeName](stream.getLimit(stream.owner.lastChild()) + this.indents.padding1 + 'px');
	}
}
//=============================================================================================================================

//=============================================================================================================================
class ProportionalCalc extends AbstractCalc {
	allocate(el, config) {
		this.setCrossSizes(el);

		var stream = this.owner,
			geom = stream.geomFromConfig(config),
			size = geom[this.geomKeys.sizeGeom] || 1;

		if (size.isNumber) el.streamProportion = size;
		else this.owner.setSavedParam(el, this.geomKeys.sizeConst, size);

		this.actualize();
	}

	reposition(el, param, val) {
		if (val.isNumber) el.streamProportion = val;
		else {
			delete el.streamProportion;
			this.owner.setSavedParam(el, param, val);
		}
		this.actualize();
	}

	actualize() {
		if (!this.owner.owner.childrenCount()) return;

		var stream = this.owner,
			division = stream.owner.divideChildren({hasProperties: 'streamProportion'});

		division.notMatch.each((a)=> this.setAlongSize(a));

		var fixSize = division.notMatch.sum(this.geomKeys.sizeName, 'px')
				+ this.indents.padding0
				+ this.indents.padding1
				+ this.indents.step * (stream.owner.childrenCount() - 1),
			allSize = stream.owner.getInnerSize(this.geomKeys.sizeConst),
			forProp = Math.max(0, allSize - fixSize);
		if (!forProp) return;

		var propPartsCount = division.match.sum('streamProportion'),
			onePart = Math.floor(forProp / propPartsCount),
			filled = 0,
			map = [];
		division.match.each((elem)=> {
			let size = Math.floor(onePart * elem.streamProportion);
			filled += size;
			map.push({elem, size});
		});
		var extraPx = forProp - filled,
			extraPxOne = Math.ceil(extraPx / map.length);

		for (var i=0; i<map.length; i++) {
			var extra = Math.min(extraPxOne, extraPx);
			extraPx -= extra;
			this.setAlongSize(
				map[i].elem,
				map[i].size + extra
			);
		}

		var pre = null,
			current = stream.owner.child(0);
		while (current) {
			stream.setParam(current, this.geomKeys.posConst, this.getPrevLim(pre));
			current.trigger('resize');
			pre = current;
			current = current.nextSibling();
		}
	}
}
//=============================================================================================================================

//=============================================================================================================================
lx.StreamPositioningStrategy.SIZE_BEHAVIOR_SCROLLING = 1;
lx.StreamPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT = 2;
lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL = 3;

lx.StreamPositioningStrategy.DEFAULT_SIZE = '25px';
