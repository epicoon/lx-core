#lx:private;

#lx:use lx.Rect as Rect;
#lx:use lx.Box as Box;

class BoxSlider extends Box #lx:namespace lx {
	build(config) {
		super.build(config);

		this.timer = new BoxSliderTimer(this, config);
		this.setSlides(config.count || 1);

		this.style('overflowX', 'hidden');
	}

	postBuild() {
		if (this.children.pre) this.children.pre.click(()=> this.timer.swapSlides(-1));
		if (this.children.post) this.children.post.click(()=> this.timer.swapSlides(1));
	}

	postUnpack() {
		this.timer = new BoxSliderTimer(this, this.lxExtract('__timer') || {});
		this.timer.slides = new lx.Collection(this.children.s);
		if (this.timer.auto) this.timer.start();
	}

	destruct() {
		this.timer.stop();
	}

	slide(num) {
		if (num >= this.children.s.len) return null;
		return this.children.s[num];
	}

	activeSlide() {
		return this.children.s[this.timer.activeSlide];
	}

	slides() {
		return this.timer.slides;
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

	setSlides(count) {
		this.timer.stop();
		this.clear();

		this.timer.slides = Box.construct(count, { key:'s', parent:this, size:[100,100] }).call('hide');

		if (count) this.timer.slides.at(0).show();
		this.timer.activeSlide = 0;
		if (count > 1) this.initButtons();

		if (this.timer.auto) this.timer.start();
	}

	initButtons() {
		new Rect({
			parent: this,
			key: 'pre',
			geom: ['0%', '40%', '5%', '20%'],
			css: 'lx-IS-button'
		}).rotate(180);
		new Rect({
			parent: this,
			key: 'post',
			geom: ['95%', '40%', '5%', '20%'],
			css: 'lx-IS-button'
		});
	}
}
//=============================================================================================================================

//=============================================================================================================================
class BoxSliderTimer extends lx.Timer {
	constructor(owner, config) {
		super(config.showTime || 3000);

		this.owner = owner;
		this.timer0 = this.periodDuration;
		this.timer1 = config.slideTime || 1000;
		this.slides = new lx.Collection();
		this.activeSlide = 0;
		this.unactiveSlide = -1;
		this.direction = 1;
		this.type = (config.type) || 'opacity';
		this.auto = (config.auto!==undefined) ? config.auto : true;
		// В каком режиме таймер: true - отображение, false - перелистывание
		this.show = true;
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
		this.direction = +dir;
		if (!this.show) {
			this.setShow(true);
			return;
		}

		this.next();
		if (this.type == 'slide') {
			this.unactive().left('0%');
			this.active().left(dir * 100 + '%');
		} else if (this.type == 'opacity') {
			this.unactive().opacity(1);
			this.active().opacity(0);
		}

		this.setShow(false);
	}

	drop() {
		this.stop();
		if (this.unactive()) this.unactive().hide();
		if (this.type == 'slide') this.active().left('0%');
		else this.active().opacity(1);
	}

	action() {
		if (!this.show) {
			var k = this.shift();
			if (this.type == 'slide') {
				k *= 100;
				this.unactive().left( -k * this.direction + '%' );
				this.active().left( this.direction * (100 - k) + '%' );
			} else if (this.type == 'opacity') {
				this.unactive().opacity(1 - k);
				this.active().opacity(k);
			}
		}

		if ( this.periodEnds() ) {
			if (!this.auto) this.setShow(true);
			else {
				if (this.show) this.swapSlides(1);
				else this.setShow(true);
			}
		}
	}
}
