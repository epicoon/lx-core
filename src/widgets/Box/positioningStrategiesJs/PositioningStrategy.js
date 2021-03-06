#lx:require IndentData;

class PositioningStrategy #lx:namespace lx {
	constructor(owner) {
		this.owner = owner;

		//TODO!!!
		this.innerFormat = self::FORMAT_FREE;
		this.defaultFormat = self::FORMAT_PERCENT;

		this.autoActualize = true;
	}

	#lx:server {
		pack() {
			var str = this.lxFullClassName
				+ ';ds:' + this.defaultFormat
				+ ';if:' + this.innerFormat;
			if (this.needJsActualize) str += ';na:1';
			var indents = this.packIndents();
			if (indents) str += ';i:' + indents;
			return str;
		}

		packIndents() {
			if (!this.indents) return false;
			return this.indents.pack();
		}
	}

	#lx:client {
		unpack(info) {
			var config = {};
			for (var i = 0, l = info.length; i < l; i++) {
				var temp = info[i].split(':');
				config[temp[0]] = temp[1];
			}
			this.innerFormat = +config.if || lx.PositioningStrategy.FORMAT_PX;
			this.defaultFormat = +config.df || lx.PositioningStrategy.FORMAT_PX;
			this.unpackProcess(config);
			if (config.i) this.unpackIndents(config.i);
			if (config.na) this.owner.__na = true;
		}

		unpackIndents(info) {
			var indents = lx.IndentData.unpackOrNull(info);
			if (indents) this.indents = indents;
		}

		unpackProcess(config) {}
	}

	actualize(info) {
		if (this.autoActualize) this.actualizeProcess(info);
	}

	onDel() {}

	/**
	 * Для позиционирования нового элемента, добавленного в контейнер
	 * */
	allocate(elem, config) {
		var geom = this.geomFromConfig(config);

		if (geom.lxEmpty) {
			elem.trigger('resize');
			return;
		}

		var abs = false;
		if (geom.l !== undefined || geom.t !== undefined || geom.r !== undefined || geom.b !== undefined) {			
			elem.addClass('lx-abspos');
			abs = true;
		}

		if (geom.w === undefined && abs) {
			geom.l = geom.l || 0;
			geom.r = geom.r || 0;
		}

		if (geom.h === undefined && abs) {
			geom.t = geom.t || 0;
			geom.b = geom.b || 0;
		}

		if (geom.lxEmpty) return;
		if ( geom.r !== undefined ) this.setParam(elem, lx.RIGHT, geom.r, true);
		if ( geom.w !== undefined ) this.setParam(elem, lx.WIDTH, geom.w, true);
		if ( geom.l !== undefined ) this.setParam(elem, lx.LEFT, geom.l, true);

		if ( geom.b !== undefined ) this.setParam(elem, lx.BOTTOM, geom.b, true);
		if ( geom.h !== undefined ) this.setParam(elem, lx.HEIGHT, geom.h, true);
		if ( geom.t !== undefined ) this.setParam(elem, lx.TOP, geom.t, true);
		elem.trigger('resize');
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
		this.owner.getChildren().each((a)=>a.trigger('resize'));
	}

	/**
	 * Сброс данных стратегии при очистке контейнера
	 * */
	reset() {}

	/**
	 * Сброс данных и влияний на владельца при смене им стратегии
	 * */
	clear() { this.reset(); }

	/**
	 * Запрос на изменение позиционного параметра для конкретного элемента
	 * Должен вернуть булево значение - true и поменять параметр, либо false и не менять параметр
	 * */
	tryReposition(elem, param, val) {
		this.setParam(elem, param, val);
		return true;		
	}

	/**
	 * Если у дочернего элемента автоматическое определение размеров, н-р у Text, здесь описывается реакция на изменившиеся размеры
	 * */
	reactForAutoresize(elem) {}

	/**
	 * Прверить можно ли владельцу поменять параметр
	 * */
	checkOwnerReposition(param) {
		return true;
	}

	/**
	 * Можно задать индивидуальный формат для геометрического параметра на конкретную стратегию:
	 * */
	setFormat(param, format=null) {
		if (format === null) {
			if (this.formats) delete this.formats[param];
			return;
		}
		if (!this.formats) this.formats = [];
		this.formats[param] = format;
	}

	/**
	 * Можно получить формат для любого геометрического параметра:
	 * */
	getFormat(param) {
		if (this.formats && param in this.formats) return this.formats[param];
		// if (param in self::formats) return self::formats[param];
		return this.innerFormat;
	}

	/**
	 * TODO
	 * Установка геометрического параметра элементу
	 * */
	setParam(elem, param, val, forse=false) {
		if (val === null) {
			elem.domElem.style(lx.Geom.geomName(param), null);
			return;
		}

		if (forse) {
			if (val.isNumber) val += this.getFormatText(this.innerFormat);
			if (lx.Geom.directionByGeom(param) == lx.HORIZONTAL)
				elem.geomPriorityH(param);
			else elem.geomPriorityV(param);
			elem.domElem.style(lx.Geom.geomName(param), val);
			return;
		}

		var splittedVal = this.splitParam(param, val);

		if (isNaN(splittedVal[0])) {
			elem.domElem.style(lx.Geom.geomName(param), 'auto');
			return;
		}

		/*
		todo - из потока вопрос пошел - если геомертия настраивает своему владельцу зависимость размера от содержимого
		тут начинается путаница если приходит % - надо как-то его вычислять уже от родителя владельца,
		но это тоже не так однозначно (как хранить, как в итоге представлять, дальше тоже идет мозговыносящая логика, которая все так и так меняет),
		нужна подробная аналитика
		*/
		elem.domElem.style([lx.Geom.geomName(param)], splittedVal[0] + this.getFormatText(splittedVal[1]));

		var format = this.getFormat(param);
		if (splittedVal[1] != format) {
			if (format != self::FORMAT_FREE) {
				this.setSavedParam(elem, param, val);
				var valName = lx.Geom.geomName(param);

				if (format == self::FORMAT_PX) {
					elem.domElem.style([lx.Geom.geomName(param)], elem[valName]('px') + 'px');
				} else {
					var parent = this.owner,
						eVal = elem[valName]('px'),
						pVal = parent[valName]('px');
					elem.domElem.style([lx.Geom.geomName(param)], (eVal * 100 / pVal) + '%');
				}
			}
		} else {
			if (elem.pos && elem.pos[param]) delete elem.pos[param];
		}
	}

	/**
	 * Сохранить параметр в том формате, который был передан
	 * */
	setSavedParam(elem, param, val) {
		if (!elem.pos) elem.pos = [];
		elem.pos[param] = val;
	}

	/**
	 * Вернуть параметр, если он был сохранен
	 * */
	getSavedParam(elem, param) {
		if (!elem.pos || !elem.pos[param]) return undefined;
		return elem.pos[param];
	}

	/**
	 * Преобразования типа PositioningStrategy.FORMAT_PERCENT => '%'
	 * */
	getFormatText(format) {
		if (format == self::FORMAT_FREE)
			format = this.innerFormat == self::FORMAT_FREE
				? this.defaultFormat
				: this.innerFormat;
		if (format == self::FORMAT_PERCENT) return '%';
		if (format == self::FORMAT_PX) return 'px';
		return '';
	}

	/**
	 * Разбивает параметры вида '50%' на [50, PositioningStrategy.FORMAT_PERCENT]
	 * */
	splitParam(param, val) {
		if (val.isNumber) return [val, this.defaultFormat];
		// if (val.isNumber) return [val, param];

		var num = parseFloat(val),
			f = val.split(num)[1];
		return [num, f=='%' ? self::FORMAT_PERCENT : self::FORMAT_PX];
	}

	/**
	 * Можно задать настройки для отступов
	 * */
	setIndents(config={}) {
		var indents = lx.IndentData.createOrNull(config);
		if (indents) this.indents = indents;
		else delete this.indents;
		return this;
	}

	/**
	 * Если настройки для отступов не заданы, будет возвращен полноценный объект настроек, заполненный нулями
	 * */
	getIndents(owner = true) {
		if (!this.indents) {
			if (this.owner.getIndents) return this.owner.getIndents();
			return lx.IndentData.getZero();
		}
		return this.indents.get(owner ? this.owner : undefined);
	}

	/**
	 * Извлекает из конфигурации позиционные параметры
	 * */
	geomFromConfig(config) {
		if (config.isArray) return this.geomFromConfig({
			left: config[0],
			right: config[1],
			width: config[2],
			height: config[3],
			top: config[4],
			bottom: config[5]
		});

		var geom = {};

		if (config.geom === true) config.geom = [
			0, 0, undefined, undefined, 0, 0
		];

		if ( config.margin ) config.geom = [
			config.margin,
			config.margin,
			undefined,
			undefined,
			config.margin,
			config.margin
		];
		if ( config.geom ) {
			geom.l = config.geom[0];
			geom.t = config.geom[1];
			geom.w = config.geom[2];
			geom.h = config.geom[3];
			geom.r = config.geom[4];
			geom.b = config.geom[5];
		}
		if (config.coords) {
			geom.l = config.coords[0];
			geom.t = config.coords[1];
			geom.r = config.coords[2];
			geom.b = config.coords[3];
		}
		if (config.size) {
			geom.w = config.size[0];
			geom.h = config.size[1];
		}
		if ( config.right  !== undefined ) geom.r = config.right;
		if ( config.bottom !== undefined ) geom.b = config.bottom;

		if ( config.width  !== undefined ) geom.w = config.width;
		if ( config.height !== undefined ) geom.h = config.height;

		if ( config.left   !== undefined ) geom.l = config.left;
		if ( config.top    !== undefined ) geom.t = config.top;

		for (var i in geom) if (geom[i] === null) delete geom[i];
		return geom;
	}
}

lx.PositioningStrategy.FORMAT_PERCENT = 1;
lx.PositioningStrategy.FORMAT_PX = 2;
lx.PositioningStrategy.FORMAT_FREE = 3;
