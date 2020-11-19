class IndentData #lx:namespace lx
{
	constructor(config = {}) {
		this.set(config);
	}

	static createOrNull(config) {
		var result = new this(config);
		if (result.lxEmpty) return null;
		return result;
	}
	
	#lx:server {
		pack() {
			var indents = this.get();
			return indents.stepX
				+ ',' + indents.stepY
				+ ',' + indents.paddingLeft
				+ ',' + indents.paddingRight
				+ ',' + indents.paddingTop
				+ ',' + indents.paddingBottom
			;
		}
	}

	//todo отптимизировать упаковку-распаковку
	#lx:client {
		static unpackOrNull(info) {
			var config = info.split(',');
			var result = new this(config);
			result.stepX = config[0];
			result.stepY = config[1];
			result.paddingLeft = config[2];
			result.paddingRight = config[3];
			result.paddingTop = config[4];
			result.paddingBottom = config[5];
			return result;
		}
	}

	set(config) {
		if (config.indent       !== undefined) this.indent       = config.indent;
		if (config.step         !== undefined) this.step         = config.step;
		if (config.stepX        !== undefined) this.stepX        = config.stepX;
		if (config.stepY        !== undefined) this.stepY        = config.stepY;
		if (config.padding       !== undefined) this.padding       = config.padding;
		if (config.paddingX      !== undefined) this.paddingX      = config.paddingX;
		if (config.paddingY      !== undefined) this.paddingY      = config.paddingY;
		if (config.paddingLeft   !== undefined) this.paddingLeft   = config.paddingLeft;
		if (config.paddingRight  !== undefined) this.paddingRight  = config.paddingRight;
		if (config.paddingTop    !== undefined) this.paddingTop    = config.paddingTop;
		if (config.paddingBottom !== undefined) this.paddingBottom = config.paddingBottom;
	}

	get(elem, format='px') {
		function part(param, dir) {
			if (elem) return elem.geomPart(param, format, dir);
			return param;
		}

		var step = [
				part(lx.getFirstDefined(this.stepX, this.step, this.indent, 0), lx.HORIZONTAL),
				part(lx.getFirstDefined(this.stepY, this.step, this.indent, 0), lx.VERTICAL)
			],
			padding = [
				[
					part(lx.getFirstDefined(this.paddingLeft,  this.paddingX, this.padding, this.indent, 0), lx.HORIZONTAL),
					part(lx.getFirstDefined(this.paddingRight, this.paddingX, this.padding, this.indent, 0), lx.HORIZONTAL)
				],
				[
					part(lx.getFirstDefined(this.paddingTop,    this.paddingY, this.padding, this.indent, 0), lx.VERTICAL),
					part(lx.getFirstDefined(this.paddingBottom, this.paddingY, this.padding, this.indent, 0), lx.VERTICAL)
				]
			]; 

		return {
			stepX: step[0],
			stepY: step[1],
			paddingLeft: padding[0][0],
			paddingRight: padding[0][1],
			paddingTop: padding[1][0],
			paddingBottom: padding[1][1]
		};
	}

	static getZero() {
		return {
			step: [0, 0],
			padding: [[0, 0], [0, 0]],
			stepX: 0,
			stepY: 0,
			paddingLeft: 0,
			paddingRight: 0,
			paddingTop: 0,
			paddingBottom: 0
		}
	}
}