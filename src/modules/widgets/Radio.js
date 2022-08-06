#lx:module lx.Radio;

#lx:use lx.Checkbox;

/**
 * @widget lx.Radio
 * @content-disallowed
 */
#lx:namespace lx;
class Radio extends lx.Checkbox {
	getBasicCss() {
		return {
			checked: 'lx-Radio-1',
			unchecked: 'lx-Radio-0'
		};
	}

	static initCss(css) {
		css.inheritClass('lx-Radio-0', 'Checkbox-shape', {
			backgroundPosition: '-1px -24px'
		}, {
			hover: 'background-position: -45px -24px',
			active: 'background-position: -69px -24px',
			disabled: 'background-position: -184px -24px'
		});
		css.inheritClass('lx-Radio-1', 'Checkbox-shape', {
			backgroundPosition: '-91px -24px'
		}, {
			hover: 'background-position: -135px -24px',
			active: 'background-position: -160px -24px',
			disabled: 'background-position: -206px -24px'
		});
	}
}
