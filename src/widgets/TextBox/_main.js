#use lx.Rect as Rect;

class TextBox extends Rect #in lx {
	preBuild(config='') {
		if (config.isString) config = {text: config};

		if (!config.key) config.key = 'text';
		if (config.text) {
			config.html = config.text;
			delete config.text;
		}

		config.width = 'auto';
		config.height = 'auto';

		return config;
	}

	/**
	 * lx.Collection c
	 * Получает коллекцию TextBox-ов
	 * */
	static adaptTextByMin(c) {
		c = lx.Collection.cast(c);
		c.call("adapt");
		var min = Infinity;
		c.each(function(a) {
			var s = parseFloat(a.DOMelem.style.fontSize);
			if (min > s) min = s;
		});
		min = min + 'px';
		c.each((a)=> a.setFontSize(min));
	}

	value(val) {
		if (val === undefined) return this.html();
		this.html(val);
		if (this.parent) this.parent.childHasAutoresized(this);
		this.trigger('resize');
	}

	setFontSize(sz) {
		this.style('fontSize', sz);
		this.parent.childHasAutoresized(this);
	}

	ellipsis() {
		//todo можно как стиль оформить
		this.style({
			overflow: 'hidden',
			whiteSpace: 'nowrap',
			textOverflow: 'ellipsis'
		});
		if (this.width() == 'auto') {
			this.width('100%');
			this.parent.childHasAutoresized(this);
		}
	}

	wrap(mode) {
		this.style('whiteSpace', mode);
	}

	adapt() {
		if (!this.parent) return this;

		var ctx = this.parent,
			text = this;

		text.DOMelem.style.top = 0;
		text.DOMelem.style.left = 0;

		var lastSz = window.screen.height,
			res = lx.Math.halfDivisionMethod(0, lastSz, function(res) {
				text.DOMelem.style.fontSize = Math.floor(res) + 'px';

				var tH = text.DOMelem.clientHeight,
					cH = ctx.DOMelem.clientHeight;
				if ( Math.floor(lastSz) == Math.floor(res) ) return 0;
				lastSz = res;

				if ( tH > cH ) return 1;
				if ( ( Math.abs(tH - cH) <= 1 ) ) return 0;
				return -1;
			});

		var tH = text.DOMelem.clientHeight,
			cH = ctx.DOMelem.clientHeight;

		if (tH + lx.textPadding > cH) {
			var fs = parseInt(text.DOMelem.style.fontSize);
			text.DOMelem.style.fontSize = Math.floor(parseInt(text.DOMelem.style.fontSize) * cH / tH) + 'px';
		}

		if ( text.DOMelem.offsettWidth <= this.DOMelem.clientWidth ) return this;

		while ( text.DOMelem.offsetWidth - 5 > this.DOMelem.clientWidth )
			text.DOMelem.style.fontSize = parseInt(text.DOMelem.style.fontSize) - 1 + 'px';

		text.DOMelem.style.top = null;
		text.DOMelem.style.left = null;
		ctx.childHasAutoresized(text);

		return this;
	}
}
