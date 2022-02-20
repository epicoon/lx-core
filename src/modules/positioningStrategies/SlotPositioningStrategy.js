#lx:module lx.SlotPositioningStrategy;

#lx:use lx.IndentData;
#lx:use lx.PositioningStrategy;

/*
количество элементов определяется заданным числом строк и колонок, либо общим числом элементов
размеры всех элементов одинаковые	
есть постоянный коэффициент отношения ширины и высоты элементов	
поддерживаются варианты выравнивания элементов (для каждой оси)	
	по центру (работают шаги между элементами, отступы от краев рассчитываются)
	прижатые к краям (работают отступы от краев, шаги между элементами рассчитываются)
	равномерно распределенные (отступы от краев и шаги между элементами рассчитываются так, чтобы быть одинаковыми)
добавление новых элементов пересчитывает размеры уже имеющимся, не влияя на размер самой коробки
todo - можно добавить фиксированную высоту, чтобы высота коробки менялась + автоопределение числа колонок
*/
class SlotPositioningStrategy extends lx.PositioningStrategy #lx:namespace lx {
	constructor(owner, config) {
		super(owner);

		this.innerFormat = lx.PositioningStrategy.FORMAT_PX;
		this.defaultFormat = lx.PositioningStrategy.FORMAT_PX;

		if (config) this.init(config);
	}

	/**
	 * @param [config = {}] {Object: {
	 *     {Number} [k = 1],
	 *     {Number} [cols = 1],
	 *     {Number} [rows],
	 *     {Number} [count],
	 *     {Number&Enum(
	 *         lx.LEFT,
	 *         lx.CENTER,
	 *         lx.RIGHT,
	 *         lx.TOP,
	 *         lx.MIDDLE,
	 *         lx.BOTTOM
	 *     )} [align],
	 *     {Class(lx.Rect)} [type = lx.Box],
	 *     #merge(lx.IndentData::constructor::config)
	 * }}
	 */
	init(config = {}) {
		this.k = config.k || 1;
		this.cols = config.cols || 1;
		if (config.align !== undefined) this.align = config.align;

		this.setIndents(config);

		var count;
		if (config.count !== undefined) {
			count = config.count;
		} else if (config.rows) {
			count = this.cols * config.rows;
		} else return;

		var type = config.type || lx.Box;
		type.construct(count, { key:'s', parent:this.owner });

		#lx:server{ this.needJsActualize = 1; }
	}

	#lx:server packProcess() {
		var str = ';k:' + this.k + ';c:' + this.cols;
		if (this.align) str += ';a:' + this.align;
		return str;
	}

	#lx:client unpackProcess(config) {
		this.k = +config.k;
		this.cols = +config.c;
		if (config.a) this.align = +config.a;
	}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		elem.addClass('lx-abspos');

		#lx:server{ super.allocate(elem, config); }
		#lx:client{ this.actualize(); }
	}

	/**
	 * Запрос на изменение позиционного параметра для конкретного элемента
	 * Должен вернуть булево значения - true и поменять параметр, либо false и не менять параметр
	 * */
	tryReposition(elem, param, val) {
		return false;
	}

	#lx:client {
		/**
		 * Актуализация позиций элементов в контейнере
		 */
		actualizeProcess() {
			var sz = this.owner.size('px'),
				rows = this.rows(),
				amt = [this.cols, rows],
				k = this.k,
				r = this.getIndents(),
				align = this.align || null,
				step = [r.stepX, r.stepY],
				marg = [[r.paddingLeft, r.paddingRight], [r.paddingTop, r.paddingBottom]],
				axe = (k*this.cols/rows > sz[0]/sz[1]) ? 0 : 1,
				axe2 = +!axe,
				cellSz = [0, 0];

			cellSz[axe] = (sz[axe] - marg[axe][0] - marg[axe][1] - step[axe] * (amt[axe] - 1)) / amt[axe];
			if (axe == 1) cellSz[axe2] = k * cellSz[axe];
			else cellSz[axe2] = cellSz[axe] / k;

			switch (align) {
				case null:
					step[axe2] = (sz[axe2] - cellSz[axe2] * amt[axe2]) / (amt[axe2] + 1);
					marg[axe2][0] = step[axe2];
					break;
				case lx.CENTER:
				case lx.MIDDLE:
					marg[axe2][0] = (sz[axe2] - cellSz[axe2] * amt[axe2] - step[axe2] * (amt[axe2] - 1)) * 0.5;
					break;
				case lx.JUSTIFY:
					step[axe2] = (sz[axe2] - cellSz[axe2] * amt[axe2] - marg[axe2][0] - marg[axe2][1]) / (amt[axe2] - 1);
					break;
				case lx.LEFT:
				case lx.TOP:
					marg[axe2][0] = marg[+!axe2][0];
					break;
				case lx.RIGHT:
				case lx.BOTTOM:
					marg[axe2][0] = sz[axe2] - (cellSz[axe2] + step[axe2]) * amt[axe2];
					break;
			}

			this.relocate(marg[0][0], marg[1][0], cellSz, step);
		}

		relocate(x0, y0, sz, step) {
			var slots = this.owner.getChildren(),
				x = x0,
				y = y0;

			for (var i=0, rows=this.rows(); i<rows; i++) {
				for (var j=0; j<this.cols; j++) {
					var slot = slots.next();
					if (!slot) return;
					this.setParam(slot, lx.LEFT, x + 'px');
					this.setParam(slot, lx.TOP, y + 'px');
					this.setParam(slot, lx.WIDTH, sz[0] + 'px');
					this.setParam(slot, lx.HEIGHT, sz[1] + 'px');
					#lx:client{ slot.checkResize(); }
					x += sz[0] + step[0];
				}
				x = x0;
				y += sz[1] + step[1];
			}
		}

		rows() {
			return Math.floor((this.owner.getChildren().len + this.cols - 1) / this.cols);
		}
	}
}
