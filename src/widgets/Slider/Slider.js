#lx:module lx.Slider;

#lx:use lx.Rect;
#lx:use lx.Box;

class Slider extends lx.Box #lx:namespace lx {
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
		var track = new lx.Rect({
			parent: this,
			key: 'track',
			css: this.basicCss.track
		});

		// бегунок
		var handle = new lx.Rect({
			parent: this,
			key: 'handle',
			css: this.basicCss.handle
		});
	}

	#lx:client postBuild(config) {
		super.postBuild(config);

		var h = this.height('px'),
			w = this.width('px'),
			handleSize = Math.min(h, w);
		this.orientation = (w > h)
			? lx.HORIZONTAL
			: lx.VERTICAL;

		var handle = this.handle();
		handle.size(handleSize+'px', handleSize+'px');
		this.locateHandle();

		if (this.orientation == lx.HORIZONTAL)
			this->track.setGeom(['0%', '17%', '100%', '66%']);
		else
			this->track.setGeom(['17%', '0%', '66%', '100%']);

		handle.move()
			.on('moveBegin', self::start)
			.on('move', self::move)
			.on('moveEnd', self::stop);

		this->track.click(self::trackClick);
	}

	//TODO предусмотреть чтобы нормально дизаблилось
	getBasicCss() {
		return {
			track: 'lx-slider-track',
			handle: 'lx-slider-handle'
		};
	}

	change(func) {
		this.on('change', func);
		return this;
	}

	handle() {
		return this.children.handle;
	}

	value(val, event) {
		if (val === undefined) {
			return this._value;
		}

		if (val > this.max) val = this.max;
		if (val < this.min) val = this.min;
		this._value = val;

		#lx:client {
			this.locateHandle();
			if (event) this.trigger('input', event);
		}

		return this;
	}

	#lx:client {
		static start(event) {
			this.parent._oldValue = this.parent._value;
		}

		static move(event) {
			this.parent.setValueByHandle(this, event);
		}

		static stop(event) {
			var oldVal = this.parent._oldValue;
			if (this.parent._value == oldVal) return;
			this.parent.trigger('change', event, oldVal);
		}

		static trackClick(event) {
			var slider = this.parent,
				handle = slider->handle,
				point = slider.globalPointToInner(event),
				crd  , param;
			if (slider.orientation == lx.HORIZONTAL) {
				crd = point.x - handle.width('px') * 0.5;
				param = 'left';
			} else {
				crd = point.y - handle.height('px') * 0.5;
				param = 'top';
			}
			handle[param](crd + 'px');
			handle.returnToParentScreen();
			var oldVal = slider.value();
			slider.setValueByHandle(handle);
			if (slider.value() != oldVal)
				slider.trigger('change', event, oldVal);
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
				val = handle.top('px');
				rangeW = this.height('px') - handle.height('px');
			}

			var locval = (val * range / rangeW) + min;
			locval = Math.floor(locval / step) * step;

			if (this._value != locval) this.value(locval, event);
		}

		locateHandle() {
			var handle = this.handle(),
				range = this.max - this.min,
				rangeW, pos;
			if ( this.orientation == lx.HORIZONTAL ) {
				rangeW = this.width('px') - handle.width('px');
				pos = (this._value - this.min) * rangeW / range;
				handle.left( pos + 'px' );
			} else {
				rangeW = this.height('px') - handle.height('px');
				pos = (this._value - this.min) * rangeW / range;
				handle.top( pos + 'px' );
			}
		}
	}
}
