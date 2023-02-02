#lx:module lx.TextBox;

#lx:use lx.Rect;

/**
 * @widget lx.TextBox
 * @content-disallowed
 */
#lx:namespace lx;
class TextBox extends lx.Rect {
	modifyConfigBeforeApply(config='') {
		if (lx.isString(config)) config = {text: config};

		if (!config.key) config.key = 'text';
		if (config.text) {
			config.html = config.text;
			delete config.text;
		}

		config.width = 'auto';
		config.height = 'auto';

		return config;
	}

	getBasicCss() {
		return 'lx-TextBox';
	}

	static initCss(css) {
		css.addClass('lx-TextBox', {
			padding: '0px 10px',
			width: 'auto',
			height: 'auto',

			fontFamily: 'inherit',
			fontSize: 'inherit',
			color: 'inherit',

			cursor: 'inherit',
			overflow: 'inherit',
			whiteSpace: 'inherit',
			textOverflow: 'inherit',
		});
	}

	text(val) {
		return this.value(val);
	}

	value(val) {
		if (val === undefined) return this.html();
		this.html(val);
		this.reportSizeHasChanged();
		#lx:client { this.checkResize(); }
	}

	setFontSize(sz) {
		this.style('fontSize', sz);
		this.reportSizeHasChanged();
	}

	ellipsis() {
		//todo можно как стиль оформить
		this.style({
			overflow: 'hidden',
			whiteSpace: 'nowrap',
			textOverflow: 'ellipsis'
		});
	}

	wrap(mode) {
		this.style('white-space', mode);
	}

	#lx:server adapt() {
		this.onPostUnpack('.adapt');
	}

	#lx:client {

		//TODO работает неправильно
		adapt() {
			if (!this.parent) return this;

			var ctx = this.parent,
				text = this;

			text.domElem.style('top', 0);
			text.domElem.style('left', 0);

			var cH = ctx.domElem.param('clientHeight');
			var lastSz = window.screen.height,
				res = lx.Math.halfDivisionMethod(0, lastSz, function(res) {
					text.domElem.style('fontSize', Math.floor(res) + 'px');
					var tH = text.domElem.param('clientHeight');
					if ( Math.floor(lastSz) == Math.floor(res) ) return 0;
					lastSz = res;

					if ( tH > cH ) return 1;
					if ( ( Math.abs(tH - cH) <= 1 ) ) return 0;
					return -1;
				});

			var tH = text.domElem.param('clientHeight'),
				cH = ctx.domElem.param('clientHeight');

			if (tH + lx.textPadding > cH) {
				var fs = parseInt(text.domElem.style('fontSize'));
				text.domElem.style('fontSize', Math.floor(parseInt(text.domElem.style('fontSize')) * cH / tH) + 'px');
			}

			if ( text.domElem.param('offsettWidth') <= this.domElem.param('clientWidth') ) return this;

			while ( text.domElem.param('offsetWidth') - 5 > this.domElem.param('clientWidth') )
				text.domElem.style.param('fontSize', parseInt(text.domElem.style('fontSize')) - 1 + 'px');

			text.domElem.style('top', null);
			text.domElem.style('left', null);
			ctx.childHasAutoresized(text);

			return this;
		}

		/**
		 * lx.Collection c
		 * Получает коллекцию TextBox-ов
		 * */
		static adaptTextByMin(c) {
			c = lx.Collection.cast(c);
			c.forEach(child=>child.adapt());
			var min = Infinity;
			c.forEach(a=>{
				var s = parseFloat(a.domElem.style('fontSize'));
				if (min > s) min = s;
			});
			min = min + 'px';
			c.forEach(a=>a.setFontSize(min));
		}
	}
}
