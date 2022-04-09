#lx:module lx.Button;

#lx:use lx.Box;

#lx:namespace lx;
class Button extends lx.Box {
	#lx:client clientBuild(config) {
		super.clientBuild(config);
		this.align(lx.CENTER, lx.MIDDLE);
		this.on('mousedown', lx.Event.preventDefault);
	}

	getBasicCss() {
		return 'lx-Button';
	}
	
	static initCssAsset(css) {
		css.inheritClass('lx-Button', 'ActiveButton');
	}
}
