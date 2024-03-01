#lx:module lx.BoxSlider;

#lx:use lx.Box;

/**
 * @widget lx.BoxSlider
 * @content-disallowed
 */
#lx:namespace lx;
class BoxSlider extends lx.Box {
	#lx:const
		TYPE_SLIDER = 1,
		TYPE_OPACITY = 2;

	getBasicCss() {
		return {
			leftButton: 'lx-IS-button-l',
			rightButton: 'lx-IS-button-r'
		};
	}

	static initCss(css) {
		css.addAbstractClass('lx-IS-button', {
			'@icon': ['\\276F', {fontSize:'calc(25px + 1.0vh)', paddingBottom:'10px'}],
			borderTopLeftRadius: css.preset.borderRadius,
			borderBottomLeftRadius: css.preset.borderRadius,
			opacity: '0.3'
		});
		css.inheritClass('lx-IS-button-l', 'lx-IS-button', {
			transform: 'rotate(180deg)',
		}, {
			hover: 'background-color: black'
		});
		css.inheritClass('lx-IS-button-r', 'lx-IS-button', {
		}, {
			hover: 'background-color: black'
		});
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [count = 1] {Number}
	 *     [type = lx.BoxSlider.TYPE_OPACITY] {Number&Enum(
	 *         lx.BoxSlider.TYPE_SLIDER,
	 *         lx.BoxSlider.TYPE_OPACITY
	 *     )},
	 *     [showDuration = 3000] {Number} (: milliseconds :),
	 *     [slideDuration = 1000] {Number} (: milliseconds :),
	 *     [auto = true] {Boolean}
	 * }}
	 */
	render(config) {
		super.render(config);

		#lx:client{ this.timer = new BoxSliderTimer(this, config); }
		#lx:server{
			var timer = {};
			if (config.type) timer.type = config.type;
			if (config.showDuration) timer.showDuration = config.showDuration;
			if (config.slideDuration) timer.slideDuration = config.slideDuration;
			if (config.auto !== null) timer.auto = config.auto;
			if (!timer.lxEmpty()) this.timer = timer;
		}

		this.setSlides(config.count || 1);
		this.style('overflowX', 'hidden');
	}

	#lx:client {
		clientRender(config) {
			super.clientRender(config);
			if (this->pre) this->pre.click(()=> this.timer.swapSlides(-1));
			if (this->post) this->post.click(()=> this.timer.swapSlides(1));
		}

		postUnpack(config) {
			super.postUnpack(config);
			this.timer = new BoxSliderTimer(this, this.timer || {});
			this.timer.slides = new lx.Collection(this->s);
			if (this.timer.auto) this.timer.start();
		}

		destruct() {
			super.destruct();
			this.timer.stop();
		}

		activeSlide() {
			return this->s[this.timer.activeSlide];
		}

		setAutoSlide(bool) {
			var timer = this.timer;
			if (bool) {
				timer.auto = true;
				timer.setShow(true);
			} else {
				timer.auto = false;
				timer.stop();
			}
			return this;
		}

		setActiveSlide(num) {
			this.slide( this.timer.activeSlide ).hide();
			this.slide(num).show();
			this.timer.activeSlide = num;
		}
	}

	slides() {
		return this->s;
	}

	slide(num) {
		if (num >= this->s.len) return null;
		return this->s[num];
	}

	setSlides(count) {
		#lx:client{ this.timer.stop(); }

		this.clear();
		var slides = lx.Box.construct(count, { key:'s', parent:this, geom:[0,0,100,100] })
			.forEach(child=>child.hide());
		if (count) slides.at(0).show();

		#lx:client {
			this.timer.slides = slides;
			this.timer.activeSlide = 0;
		}

		if (count > 1) this.initButtons();

		#lx:client{ if (this.timer.auto) this.timer.start(); }
	}

	initButtons() {
		new lx.Rect({
			parent: this,
			key: 'pre',
			geom: ['0%', '40%', '5%', '20%'],
			css: this.basicCss.leftButton
		});
		new lx.Rect({
			parent: this,
			key: 'post',
			geom: ['95%', '40%', '5%', '20%'],
			css: this.basicCss.rightButton
		});
	}
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * PRIVATE
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

#lx:client {
	class BoxSliderTimer extends lx.Timer {
		constructor(owner, config) {
			super(config.showDuration || 3000);

			this.owner = owner;
			this.timer0 = this.periodDuration;
			this.timer1 = config.slideDuration || 1000;
			this.slides = new lx.Collection();
			this.activeSlide = 0;
			this.unactiveSlide = -1;
			this.direction = 1;
			this.type = (config.type) || lx.BoxSlider.TYPE_OPACITY;
			this.auto = (config.auto!==undefined) ? config.auto : true;
			// В каком режиме таймер: true - отображение, false - перелистывание
			this.show = true;

			this.whileCycle(this.onFrame);
		}

		active() {
			return this.slides.at(this.activeSlide);
		}

		unactive() {
			return this.slides.at(this.unactiveSlide);
		}

		next() {
			this.active().hide();
			if (this.unactive()) this.unactive().hide();
			this.unactiveSlide = +this.activeSlide;
			this.activeSlide = this.unactiveSlide + this.direction;
			if (this.activeSlide < 0) this.activeSlide = this.slides.len - 1;
			else if (this.activeSlide >= this.slides.len) this.activeSlide = 0;
			this.active().show();
			this.unactive().show();
		}

		setShow(show) {
			this.show = show;
			if (show) {
				this.drop();
				this.periodDuration = this.timer0;
				if (this.auto) this.start();
			} else {
				this.periodDuration = this.timer1;
				this.start();
			}
		}

		swapSlides(dir) {
			this.resetTime();
			this.direction = +dir;
			if (!this.show) {
				this.setShow(true);
				return;
			}

			this.next();
			if (this.type == lx.BoxSlider.TYPE_SLIDER) {
				this.unactive().left('0%');
				this.active().left(dir * 100 + '%');
			} else if (this.type == lx.BoxSlider.TYPE_OPACITY) {
				this.unactive().opacity(1);
				this.active().opacity(0);
			}

			this.setShow(false);
		}

		drop() {
			this.stop();
			if (this.unactive()) this.unactive().hide();
			if (this.type == lx.BoxSlider.TYPE_SLIDER) this.active().left('0%');
			else this.active().opacity(1);
		}

		onFrame() {
			if (!this.show) {
				var k = this.shift();
				if (this.type == lx.BoxSlider.TYPE_SLIDER) {
					k *= 100;
					this.unactive().left( -k * this.direction + '%' );
					this.active().left( this.direction * (100 - k) + '%' );
				} else if (this.type == lx.BoxSlider.TYPE_OPACITY) {
					this.unactive().opacity(1 - k);
					this.active().opacity(k);
				}
			}

			if ( this.isCycleEnd() ) {
				if (!this.auto) this.setShow(true);
				else {
					if (this.show) this.swapSlides(1);
					else this.setShow(true);
				}
			}
		}
	}
}
