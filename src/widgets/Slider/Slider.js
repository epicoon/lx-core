#lx:use lx.Rect as Rect;
#lx:use lx.Box as Box;

class Slider extends Box #lx:namespace lx {
	build(config) {
		super.build(config);

		this.min = config.min || 0;
		this.max = config.max || 100;
		this.step = config.step || 1;

		var value = config.value || 0;
		if (value < this.min) value = this.min;
		if (value > this.max) value = this.max;
		this._value = value;

		this.style('overflow', 'visible');

		// трек
		var track = new Rect({
			parent: this,
			key: 'track',
			geom: (this.width('px') > this.height('px'))
				? ['0%', '17%', '100%', '66%']
				: ['17%', '0%', '66%', '100%'],
			css: 'lx-slider-track'
		});

		// бегунок
		var handle = new Rect({
			parent: this,
			key: 'handle',
			css: 'lx-slider-handle'
		});
	}

	postBuild(config) {
		super.postBuild(config);
		var handle = this.handle(),
			h = this.height('px'),
			w = this.width('px'),
			handleSize = Math.min(h, w);
		this.orientation = (w > h)
			? lx.HORIZONTAL
			: lx.VERTICAL;
		handle.size(handleSize+'px', handleSize+'px');
		if (this.orientation == lx.HORIZONTAL) handle.top(0);
		else handle.top(h - handleSize);

		this.value(this._value);
		handle.move()
			.on('move', self::move)
			.on('moveEnd', self::stop);
	}

	change(func) {
		this.on('change', func);
		return this;
	}

	static move(event) {
		this.parent.setValueByHandle( this, event );
	}

	static stop(event) {
		this.parent.trigger( 'change', event );
	}

	handle() {
		return this.children.handle;
	}

	value(val, event) {
		if (val === undefined) {
			return this._value;
		}

		var handle = this.handle(),
			min = this.min,
			max = this.max;
		if (val > max) val = max;
		if (val < min) val = min;

		this._value = val;

		var step = this.step,
			range = max - min,
			rangeW, pos;
		if ( this.orientation == lx.HORIZONTAL ) {
			rangeW = this.width('px') - handle.width('px');
			pos = (val - this.min) * rangeW / range;
			handle.left( pos + 'px' );
		} else {
			rangeW = this.height('px') - handle.height('px');
			pos = (val - this.min) * rangeW / range;
			handle.top( this.height('px') - handle.height('px') - pos + 'px' );
		}

		if (event) this.trigger('input', event);

		return this;
	}

	setValueByHandle(handle, event) {
		var val, rangeW,
			min = this.min,
			max = this.max,
			step = this.step,
			range = max - min;

		if (this.orientation == lx.HORIZONTAL) {
			val = handle.left('px');
			rangeW = this.width('px') - handle.width('px');
		} else {
			val = handle.bottom('px');
			rangeW = this.height('px') - handle.height('px');
		}

		var locval = (val * range / rangeW) + min;
		locval = Math.floor(locval / step) * step;

		if (this._value != locval) this.value(locval, event);
	}
}
