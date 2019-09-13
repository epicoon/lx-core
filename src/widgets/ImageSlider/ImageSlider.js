#lx:module lx.ImageSlider;

#lx:use lx.BoxSlider;
#lx:use lx.Image;

class ImageSlider extends lx.BoxSlider #lx:namespace lx {
	build(config) {
		super.build(config);

		this.setImages(config.images || []);
	}

	setImages(images) {
		if (this.slides().len != images.len)
			this.setSlides(images.len);

		this.slides().each((a, i)=> a.add(lx.Image, {size:['100%', '100%'], filename:images[i]}));
		return this;
	}
}