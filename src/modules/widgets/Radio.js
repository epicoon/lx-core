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
		css.addClass('lx-Radio-0', {
			border: 'solid #61615e 1px',
			width: '16px',
			height: '16px',
			borderRadius: '50%',
			backgroundColor: 'white',
			cursor: 'pointer'
		}, {
			hover: {
				boxShadow: '0 0 6px ' + css.preset.widgetIconColor,
			},
			active: {
				backgroundColor: '#dedede',
				boxShadow: '0 0 8px ' + css.preset.widgetIconColor,
			}
		});
		css.inheritClass('lx-Radio-1', 'lx-Radio-0', {
			color: 'black',
			'@icon': ['\\25CF', {fontFamily:'main', fontSize:8, paddingBottom:'1px'}],
		});
	}
}
