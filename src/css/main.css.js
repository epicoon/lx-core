#lx:use lx.CssColorSchema;
#lx:use lx.MainCssContext;

const cssContext = new lx.CssContext();
cssContext.useContext(lx.MainCssContext.instance);

cssContext.addClass('lxbody', {
	position: 'absolute',
	left: '0%',
	top: '0%',
	width: '100%',
	height: '100%',
	overflow: 'hidden',
	color: lx.CssColorSchema.textColor,
	backgroundColor: lx.CssColorSchema.mainBackgroundColor
});

cssContext.addClass('lx-abspos', {
	position: 'absolute'
});

cssContext.addClass('lxps-grid-v', {
	display: 'grid',
	gridAutoFlow: 'row',
	gridTemplateColumns: '1fr',
	gridAutoRows: 'auto'
});
cssContext.addClass('lxps-grid-h', {
	display: 'grid',
	gridAutoFlow: 'column',
	gridTemplateRows: '1fr',
	gridAutoColumns: 'auto'
});

cssContext.addClass('lx-ellipsis', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis'
});

cssContext.addStyle('input', {
	overflow: 'hidden',
	visibility: 'inherit',
	boxSizing: 'border-box'
});

cssContext.addStyle('div', {
	overflow: 'visible',
	visibility: 'inherit',
	boxSizing: 'border-box',
	color: 'inherit'
});

cssContext.addStyle('@font-face', {
	fontFamily: 'MainFont',
	src: 'url("font/Muli-VariableFont_wght.ttf") format("truetype")',
	fontStyle: 'normal',
	fontWeight: 600
});

cssContext.inheritClass('lx-Box', 'AbstractBox');

return cssContext.toString();
