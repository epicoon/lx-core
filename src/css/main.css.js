function initCss(css) {
	css.addClass('lxbody', {
		position: 'absolute',
		left: '0%',
		top: '0%',
		width: '100%',
		height: '100%',
		overflow: 'hidden',
		color: css.preset.textColor,
		backgroundColor: css.preset.mainBackgroundColor
	});

	css.addClass('lx-abspos', {
		position: 'absolute'
	});

	css.addClass('lxps-grid-v', {
		display: 'grid',
		gridAutoFlow: 'row',
		gridTemplateColumns: '1fr',
		gridAutoRows: 'auto'
	});
	css.addClass('lxps-grid-h', {
		display: 'grid',
		gridAutoFlow: 'column',
		gridTemplateRows: '1fr',
		gridAutoColumns: 'auto'
	});

	css.addClass('lx-ellipsis', {
		overflow: 'hidden',
		whiteSpace: 'nowrap',
		textOverflow: 'ellipsis'
	});

	css.addStyle('input', {
		overflow: 'hidden',
		visibility: 'inherit',
		boxSizing: 'border-box'
	});

	css.addStyle('div', {
		overflow: 'visible',
		visibility: 'inherit',
		boxSizing: 'border-box',
		color: 'inherit'
	});

	css.addStyle('@font-face', {
		fontFamily: 'MainFont',
		src: 'url("font/Muli-VariableFont_wght.ttf") format("truetype")',
		fontStyle: 'normal',
		fontWeight: 600
	});
}
