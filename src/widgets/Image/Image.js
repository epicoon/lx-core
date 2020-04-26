#lx:module lx.Image;

#lx:use lx.Rect;

class Image extends lx.Rect #lx:namespace lx {
	modifyConfigBeforeApply(config) {
		if (config.isString) config = {filename: config};
		if (!config.key) config.key = 'image';
		return config;
	}

	/**
	 * config = {
	 *	// стандартные для Rect,
	 *	
	 *	src: string  // путь от корня сайта
	 *	filename: string  // путь относительно настроек текущего модуля
	 * }
	 */
	build(config) {
		super.build(config);

		var src = config.src || null;
		if (config.filename) src = this.imagePath(config.filename);

		this.setAttribute('onload', 'this.setAttribute(\'loaded\', 1)');
		this.source(src);
	}

	static getStaticTag() {
		return 'img';
	}

	load(func) {
		this.on('load', func);
	}

	source(url) {
		this.setAttribute('loaded', 0);
		this.domElem.setAttribute('src', url);
		return this;
	}

	picture(url) {
		this.source(this.imagePath(url));
		return this;
	}

	value(src) {
		if (src === undefined) return this.domElem.param('src');
		this.source(src);
	}

	#lx:client {
		isLoaded() {
			var elem = this.getDomElem();
			if (!elem) return false;
			return !!+this.getAttribute('loaded');
		}

		adapt() {
			var elem = this.getDomElem();
			if (!elem) {
				this.domElem.addAction('adapt');
				return this;
			}

			function scale() {
				var container = this.parent.getContainer().getDomElem(),
					sizes = lx.Geom.scaleBar(
						container.offsetHeight,
						container.offsetWidth,
						elem.naturalHeight,
						elem.naturalWidth
					);
				this.width(sizes[1] + 'px');
				this.height(sizes[0] + 'px');
				this.off('load', scale);
			};
			
			if (this.isLoaded()) scale.call(this);
			else this.on('load', scale);
			return this;
		}
	}

	#lx:server adapt() {
		this.onload('.adapt');
	}
}