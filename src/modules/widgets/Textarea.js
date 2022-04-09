#lx:module lx.Textarea;

#lx:use lx.Input;

#lx:namespace lx;
class Textarea extends lx.Input {
	static getStaticTag() {
		return 'textarea';
	}

	getBasicCss() {
		return 'lx-Textarea';
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Textarea', 'Input', {
			resize: 'none'
		});
	}
}
