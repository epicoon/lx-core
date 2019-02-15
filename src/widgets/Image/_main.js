#lx:use lx.Rect as Rect;

class Image extends Rect #lx:namespace lx {
	preBuild(config) {
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
	 * */
	build(config) {
		super.build(config);

		var src = config.src || null;
		if (config.filename) src = this.imagePath(config.filename);

		this.source(src);
	}

	tagForDOM() {
		return 'img';
	}

	isLoaded() {
		return (this.DOMelem.naturalWidth != 0 || this.DOMelem.naturalHeight != 0);
	}

	source(url) {
		var reg = new RegExp(url + '$');
		if (this.DOMelem.src.match(reg)) return this;
		this.DOMelem.src = url;
		return this;
	}

	picture(url) {
		this.source(this.imagePath(url));
		return this;
	}

	value(src) {
		if (src === undefined) return this.DOMelem.src;
		this.source(src);
	}

	adapt() {
		function scale() {
			var container = this.parent.DOMelem,
				sizes = lx.Geom.scaleBar(container.offsetHeight, container.offsetWidth, this.DOMelem.naturalHeight, this.DOMelem.naturalWidth);
			this.width(sizes[1] + 'px');
			this.height(sizes[0] + 'px');
			this.off('load', scale);
		};
		
		if (this.isLoaded()) scale.call(this);
		else this.on('load', scale);
		return this;
	}
}