#lx:use lx.BoxSlider as BoxSlider;

class ImageSlider extends BoxSlider #lx:namespace lx {
	build(config) {
		super.build(config);

		this.setImages(config.images || []);
	}

	setImages(images) {
		if (this.slides().len != images.len)
			this.setSlides(images.len);

		this.slides().each((a, i)=> a.picture(images[i]));
		return this;
	}
}