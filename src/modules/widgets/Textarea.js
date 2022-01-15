#lx:module lx.Textarea;

#lx:use lx.MainCssContext;
#lx:use lx.Input;

class Textarea extends lx.Input #lx:namespace lx {
	static getStaticTag() {
		return 'textarea';
	}

	getBasicCss() {
		return 'lx-Textarea';
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Textarea', lx.MainCssContext.getClass('Input'), {
			resize: 'none'
		});
	}
}
