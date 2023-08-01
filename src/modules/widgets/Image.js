#lx:module lx.Image;

#lx:use lx.Rect;

/**
 * @widget lx.Image
 * @content-disallowed
 *
 * @event load
 * @event scale
 */
#lx:namespace lx;
class Image extends lx.Rect {
	modifyConfigBeforeApply(config) {
		if (lx.isString(config)) config = {filename: config};
		if (!config.key) config.key = 'image';
		return config;
	}

	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.Rect::constructor::config),
	 *     [src] {String} (: path to image file relative to site root :),
	 *     [filename] {String} (: path to image file relative to current plugin path :)
	 * }}
	 */
	build(config) {
		super.build(config);

		var src = config.src || null;
		if (config.filename) src = this.imagePath(config.filename);

		this.setAttribute('onLoad', 'this.setAttribute(\'loaded\', 1)');
		this.source(src);
	}

	static getStaticTag() {
		return 'img';
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
				this.trigger('scale');
			};
			
			if (this.isLoaded()) scale.call(this);
			else this.on('load', scale);
			return this;
		}
	}

	#lx:server adapt() {
		this.onLoad('.adapt');
	}
}