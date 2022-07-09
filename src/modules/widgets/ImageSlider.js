#lx:module lx.ImageSlider;

#lx:use lx.BoxSlider;
#lx:use lx.Image;

/**
 * @widget lx.ImageSlider
 * @content-disallowed
 */
#lx:namespace lx;
class ImageSlider extends lx.BoxSlider {
	/**
	 * @widget-init
	 *
	 * @param [config] {Object: {
	 *     #merge(lx.BoxSlider::build::config),
	 *     [images] {Array<String>}
	 * }}
	 */
	build(config) {
		super.build(config);
		this.setImages(config.images || []);
	}

	setImages(images) {
		if (this.slides().len != images.len)
			this.setSlides(images.len);

		this.slides().forEach((a, i)=> a.add(lx.Image, {size:['100%', '100%'], filename:images[i]}));
		return this;
	}
}