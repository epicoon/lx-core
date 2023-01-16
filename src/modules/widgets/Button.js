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
		this.on('mousedown', lx.preventDefault);
		this.setEllipsisHint({css: this.basicCss.hint});
	}

	getBasicCss() {
		return {
			main: 'lx-Button',
			hint: 'lx-Button-hint',
		};
	}
	
	static initCss(css) {
		css.inheritClass('lx-Button', 'ActiveButton');
		css.inheritClass('lx-Button-hint', 'AbstractBox', {
			padding: '10px'
		});
	}
}
