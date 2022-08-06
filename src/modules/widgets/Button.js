#lx:module lx.Button;

#lx:use lx.Box;

/**
 * @widget lx.Button
 * @content-disallowed
 */
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
	
	static initCss(css) {
		css.inheritClass('lx-Button', 'ActiveButton');
	}
}
