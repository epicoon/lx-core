#lx:require IndentData;

class PositioningStrategy #lx:namespace lx {
	constructor(owner) {
		this.owner = owner;
		this.autoActualize = true;
	}

	init(config) {
		// abstract
	}

	#lx:server {
		pack() {
			var str = this.lxFullClassName();
			if (this.needJsActualize) str += ';na:1';
			var indents = this.packIndents();
			if (indents) str += ';i:' + indents;
			return str + this.packProcess();
		}

		packIndents() {
			if (!this.indents) return false;
			return this.indents.pack();
		}

		packProcess() {
			return '';
		}
	}

	#lx:client {
		unpack(info) {
			var config = {};
			for (var i = 0, l = info.length; i < l; i++) {
				var temp = info[i].split(':');
				config[temp[0]] = temp[1];
			}
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
	 */
	allocate(elem, config) {
		var geom = this.geomFromConfig(config);

		if (geom.lxEmpty()) {
			elem.trigger('resize');
			return;
		}

		var abs = false;
		if (geom.l !== undefined || geom.t !== undefined || geom.r !== undefined || geom.b !== undefined) {			
			elem.addClass('lx-abspos');
			abs = true;
		}

		for (var i in geom) {
			if (geom[i] && geom[i].isString && geom[i].includes('/')) {
				var parts = geom[i].split('/');
				geom[i] = Math.round(100 * parts[0] / parts[1]);
			}
		}

		if (geom.w === undefined && abs) {
			geom.l = geom.l || 0;
			geom.r = geom.r || 0;
		}

		if (geom.h === undefined && abs) {
			geom.t = geom.t || 0;
			geom.b = geom.b || 0;
		}

		if (geom.lxEmpty()) return;
		if ( geom.l !== undefined ) this.setParam(elem, lx.LEFT, geom.l);
		if ( geom.r !== undefined ) this.setParam(elem, lx.RIGHT, geom.r);
		if ( geom.w !== undefined ) this.setParam(elem, lx.WIDTH, geom.w);

		if ( geom.t !== undefined ) this.setParam(elem, lx.TOP, geom.t);
		if ( geom.b !== undefined ) this.setParam(elem, lx.BOTTOM, geom.b);
		if ( geom.h !== undefined ) this.setParam(elem, lx.HEIGHT, geom.h);
		elem.trigger('resize');
	}

	/**
	 * Актуализация позиций элементов в контейнере
	 */
	actualizeProcess() {
		//TODO а если размер не менялся?
		this.owner.getChildren().each((a)=>a.trigger('resize'));
	}

	/**
	 * Сброс данных стратегии при очистке контейнера
	 */
	reset() {}

	/**
	 * Сброс данных и влияний на владельца при смене им стратегии
	 */
	clear() { this.reset(); }

	/**
	 * Запрос на изменение позиционного параметра для конкретного элемента
	 * Должен вернуть булево значение - true и поменять параметр, либо false и не менять параметр
	 */
	tryReposition(elem, param, val) {
		this.setParam(elem, param, val);
		return true;		
	}

	/**
	 * Если у дочернего элемента автоматическое определение размеров, н-р у Text, здесь описывается реакция на изменившиеся размеры
	 */
	reactForAutoresize(elem) {}

	/**
	 * Установка геометрического параметра элементу
	 */
	setParam(elem, param, val) {
		if (val === null) {
			elem.domElem.style(lx.Geom.geomName(param), null);
			return;
		}

		if (val.isNumber) val += '%';
		elem.setGeomPriority(param);
		elem.domElem.style(lx.Geom.geomName(param), val);
	}

	/**
	 * Можно задать настройки для отступов
	 */
	setIndents(config={}) {
		var indents = lx.IndentData.createOrNull(config);
		if (indents) this.indents = indents;
		else delete this.indents;
		return this;
	}

	/**
	 * Если настройки для отступов не заданы, будет возвращен полноценный объект настроек, заполненный нулями
	 */
	getIndents(owner = true) {
		if (!this.indents) {
			if (this.owner.getIndents) return this.owner.getIndents();
			return lx.IndentData.getZero();
		}
		return this.indents.get(owner ? this.owner : undefined);
	}

	/**
	 * Извлекает из конфигурации позиционные параметры
	 */
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
