#lx:module lx.Button;

#lx:use lx.MainCssContext;
#lx:use lx.Box;

class Button extends lx.Box #lx:namespace lx {
	#lx:client clientBuild(config) {
		super.clientBuild(config);
		this.align(lx.CENTER, lx.MIDDLE);
		this.on('mousedown', lx.Event.preventDefault);
	}

	getBasicCss() {
		return 'lx-Button';
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Button', lx.MainCssContext.getClass('ActiveButton'));
	}
}
