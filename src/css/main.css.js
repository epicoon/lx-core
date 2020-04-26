/* TextBox */
/* Button */
/* Input */
/* Textarea */
/* Dropbox */
/* Table */
/* Slider */
/* LabeledGroup */
/* Checkbox */
/* Radio */
/* BoxSlider */
/* Calendar */
/* TreeBox */
/* MultiBox */
/* Paginator */

/* TableManager */

/*
TODO - нужны функции преобразования цветов по аналогии с LESS

var buttonBorderColor = darken(aldenTurquoise, '15%');
var intactGradient = 'linear-gradient(' + [
	'to bottom',
	lighten(aldenTurquoise, '10%'),
	darken(aldenTurquoise, '10%')
].join(',') + ')';
var activeGradient = 'linear-gradient(' + [
	'to bottom',
	lighten(aldenRed, '10%'),
	darken(aldenRed, '10%')
].join(',') + ')';

Ссылки, которые находил - не очень помогли, но самое близкое из найденного
http://compgraph.tpu.ru/Oglavlenie.htm
https://habr.com/ru/post/181580/
https://habr.com/ru/post/202966/
*/

var hoverColor = '#FFFF99';

var aldenTurquoise = '#1C8382';
var aldenRed = '#DC2E2F';

var buttonColor = 'white';
var buttonTextShadow = 'black';
var buttonBorderColor = '#0f4443';

var intactGradient = 'linear-gradient(to bottom, #25adac, #135958)';
var hoverGradient = 'linear-gradient(to bottom, #ff8, #dd8)';
var activeGradient = 'linear-gradient(to bottom, #e35a5a, #b81f20)';

var cssList = new lx.CssContext();


cssList.addStyle('body', {
	display: 'block',
	margin: '0px',
	padding: '0px',
	overflow: 'auto'//!!!hidden
});

cssList.addClass('lxbody', {
	position: 'absolute',
	left: '0%',
	top: '0%',
	width: '100%',
	height: '100%',
	overflow: 'auto'//!!!hidden
});

cssList.addClass('lx-abspos', {
	position: 'absolute'
});

cssList.addClass('lxps-grid-v', {
	display: 'grid',
	gridAutoFlow: 'row',
	gridTemplateColumns: '1fr',
	gridAutoRows: 'auto'
});
cssList.addClass('lxps-grid-h', {
	display: 'grid',
	gridAutoFlow: 'column',
	gridTemplateRows: '1fr',
	gridAutoColumns: 'auto'
});


cssList.addStyle(['div', 'input'], {
	overflow: 'hidden',
	visibility: 'inherit',
	boxSizing: 'border-box'
});


/*============================================================================*/
/* TextBox */
cssList.addClass('lx-TextBox', {
	padding: '0px 5px',
	width: 'auto',
	height: 'auto',
	//color: '#333333',
	//whiteSpace: 'pre',
	cursor: 'inherit',
	// fontFamily: 'helvetica, arial, sans-serif'
	fontFamily: 'TestFont'
});
/* TextBox */
/*============================================================================*/


/*============================================================================*/
/* Button */
cssList.addClass('lx-Button', {
	fontWeight: 'bold',
	color: buttonColor,
	background: 'white',
	backgroundImage: intactGradient,
	boxShadow: '0 1px 2px rgba(0, 0, 0, 0.3)',
	textShadow: '0 1px 1px ' + buttonTextShadow,
	borderRadius: '8px',
	border: '1px solid ' + buttonBorderColor
}, {
	'not([disabled])': {
		cursor: 'pointer'
	},
	'hover:not([disabled])': {
		backgroundColor: '#D8D8D8',
		boxShadow: '0px 0px 10px #888888'
	},
	'active:not([disabled])': {
	    backgroundImage: activeGradient
	},
	disabled: {
		opacity: '0.5'
	}
});
/* Button */
/*============================================================================*/


/*============================================================================*/
/* Input */
cssList.addAbstractClass('Input', {
	border: '1px solid #ccc',
	padding: '4px 5px',
	background: '#ffffff !important',
	borderRadius: '8px',
	outline: 'none',
	boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.2)',
	fontFamily: 'TestFont'
});

cssList.inheritClass('lx-Input', 'Input', {
}, {
	focus: 'border: 1px solid #888',
	disabled: 'opacity: 0.5'
});
/* Input */
/*============================================================================*/


/*============================================================================*/
/* Textarea */
cssList.inheritClass('lx-Textarea', 'Input', {
	resize: 'none'
});
/* Textarea */
/*============================================================================*/


/*============================================================================*/
/* Dropbox */
cssList.addClass('lx-Dropbox', {
	borderRadius: '8px',
	cursor: 'pointer',
}, {
	disabled: 'opacity: 0.5'
});

cssList.addClass('lx-Dropbox-input-wrapper', {
	display: 'inline-block',
	width: 'calc(100% - 30px)',
	height: '100%',
});
cssList.addClass('lx-Dropbox-input', {
	width: '100%',
	height: '100%',
	borderTopRightRadius: 0,
	borderBottomRightRadius: 0
});

cssList.addClass('lx-Dropbox-but', {
	display: 'inline-block',
	width: '30px',
	height: '100%',
	borderTop: '1px solid #ccc',
	borderBottom: '1px solid #ccc',
	borderRight: '1px solid #ccc',
	backgroundImage: 'url(img/dropbut.png)',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%'
}, {
	hover: 'opacity: 0.7'
});

cssList.addClass('lx-Dropbox-cell', {
}, {
	hover: 'background-color:' + hoverColor
});
/* Dropbox */
/*============================================================================*/


/*============================================================================*/
/* Table */
var tableBorderColor = '#D8D8D8';

cssList.addClass('lx-Table', {
	border: '1px solid ' + tableBorderColor,
	borderRadius: '8px'
});

cssList.addClass('lx-Table-row', {
	borderTop: '1px solid ' + tableBorderColor
}, {
	'first-child': 'border: 0px',
	'nth-child(2n)': 'background-color: #F6F6F6',
	'nth-child(2n+1)': 'background-color: white'
});

cssList.addClass('lx-Table-cell', {
	height: '100%',
	borderRight: '1px solid ' + tableBorderColor
}, {
	'last-child': 'border: 0px'
});

//TODO - после переделки lx.Box.entry это не надо будет
cssList.addStyle('.lx-Table-cell .lx-Textarea', {
	borderRadius: '0px'
});
/* Table */
/*============================================================================*/


/*============================================================================*/
/* Slider */
cssList.addClass('lx-slider-track', {
	backgroundColor: '#EEEEEE',
	border: '#CCCCCC solid 1px',
	borderRadius: '5px',
	cursor: 'pointer'
});

cssList.addClass('lx-slider-handle', {
	backgroundColor: '#F6F6F6',
	border: '#CCCCCC solid 1px',
	borderRadius: '5px',
	cursor: 'pointer'
}, {
	hover: {
		backgroundColor: '#FDFCCE',
		borderColor: '#FBCB09'
	},
	active: {
		backgroundColor: '#FDFCCE',
		borderColor: '#FBCB09'
	}
});
/* Slider */
/*============================================================================*/


/*============================================================================*/
/* LabeledGroup */
cssList.addClass('lx-LabeledGroup', {
	display: 'grid',
	gridAutoFlow: 'row',
	gridGap: '.8em',
	padding: '1.2em'
});

cssList.addClass('lx-LabeledGroup-item', {
	position: 'relative',
	gridRow: 'auto'
});

cssList.addClass('lx-LabeledGroup-label', {
	color: '#000000'
});
/* LabeledGroup */
/*============================================================================*/


/*============================================================================*/
/* Checkbox */
var checkboxSprite = 'url(img/crsprite.png)';

cssList.addAbstractClass('Checkbox-shape', {
	width: '23px !important',
	height: '23px !important',
	backgroundImage: checkboxSprite,
	cursor: 'pointer'
});

cssList.inheritClass('lx-Checkbox-0', 'Checkbox-shape', {
	backgroundPosition: '-2px -3px'
}, {
	hover: 'background-position: -46px -3px',
	active: 'background-position: -70px -3px',
	disabled: 'background-position: -184px -3px'
});

cssList.inheritClass('lx-Checkbox-1', 'Checkbox-shape', {
	backgroundPosition: '-92px -3px'
}, {
	hover: 'background-position: -135px -3px',
	active: 'background-position: -160px -3px',
	disabled: 'background-position: -206px -3px'
});
/* CheckBox */
/*============================================================================*/


/*============================================================================*/
/* Radio */
cssList.inheritClass('lx-Radio-0', 'Checkbox-shape', {
	backgroundPosition: '-1px -24px'
}, {
	hover: 'background-position: -45px -24px',
	active: 'background-position: -69px -24px',
	disabled: 'background-position: -184px -24px'
});

cssList.inheritClass('lx-Radio-1', 'Checkbox-shape', {
	backgroundPosition: '-91px -24px'
}, {
	hover: 'background-position: -135px -24px',
	active: 'background-position: -160px -24px',
	disabled: 'background-position: -206px -24px'
});
/* Radio */
/*============================================================================*/


/*============================================================================*/
/* BoxSlider */
cssList.addAbstractClass('lx-IS-button', {
	backgroundImage: 'url(img/ISarroy.png)',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%',
	opacity: '0.3'
});

cssList.inheritClass('lx-IS-button-l', 'lx-IS-button', {
	transform: 'rotate(180deg)'
}, {
	hover: 'opacity: 0.5'
});

cssList.inheritClass('lx-IS-button-r', 'lx-IS-button', {
}, {
	hover: 'opacity: 0.5'
});
/* BoxSlider */
/*============================================================================*/


/*============================================================================*/
/* Calendar */
var calendarSideBackground = 'linear-gradient(to bottom, #9AD9F7, #146FBB)';
var calendarSideTextColor = 'white';

cssList.inheritClass('lx-Calendar', 'lx-Input');

cssList.addClass('lx-Calendar-arroy', {
	backgroundImage: 'url(img/calendarArroy.png)',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%',
	cursor: 'pointer'
}, {
	hover: {
		top: '-7px !important',
		height: '45px !important'
	}
});

cssList.addClass('lx-Calendar-day-of-week', {
	backgroundImage: calendarSideBackground,
	color: calendarSideTextColor,
	fontWeight: 'bold'
});

cssList.inheritClass('lx-Calendar-today', 'lx-Calendar-day-of-week', {
	borderBottomLeftRadius: '8px',
	borderBottomRightRadius: '8px',
	cursor: 'pointer'
});

cssList.inheritClass('lx-Calendar-cell-today', 'lx-Calendar-day-of-week', {
	cursor: 'pointer'
});

cssList.addClass('lx-Calendar-cell-day', {
	cursor: 'pointer'
});

cssList.addClass('lx-Calendar-menu');
cssList.addStyleGroup('.lx-Calendar-menu', {
	'.lx-Table' : 'border-radius: 0px',
	'.lx-Calendar-cell-day:hover': 'font-weight: bold'
});
/* Calendar */
/*============================================================================*/


/*============================================================================*/
/* TreeBox */
cssList.addClass('lx-TreeBox', {
	backgroundColor: '#D8D8D8',
	borderRadius: '10px'
});

cssList.addClass('lx-TW-Button', {
	borderRadius: '5px',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%'
}, {
	hover: 'box-shadow: 0px 0px 7px #888888'
});

cssList.addClasses({
	'lx-TW-Button-closed': 'background-image: url(img/TWclosed.png)',
	'lx-TW-Button-opened': 'background-image: url(img/TWopened.png)',
	'lx-TW-Button-empty' : 'background-image: url(img/TWempty.png)',
	'lx-TW-Button-add'   : 'background-image: url(img/TWadd.png)',
	'lx-TW-Button-del'   : 'background-image: url(img/TWdel.png)'
});

cssList.addClass('lx-TW-Label', {
	backgroundColor: '#FFFFFF',
	borderRadius: '5px'
});
/* TreeBox */
/*============================================================================*/


/*============================================================================*/
/* MultiBox */
cssList.addClass('lx-MultiBox', {
	border: '1px solid ' + tableBorderColor,
	borderTopWidth: '0px',
	borderRadius: '4px'
});

cssList.addClass('lx-MultiBox-mark', {
	fontWeight: 'bold',
	color: buttonColor,
	boxShadow: '0 1px 2px rgba(0, 0, 0, 0.3)',
	textShadow: '0 1px 1px ' + buttonTextShadow,
	backgroundImage: intactGradient,
	border: '1px solid ' + buttonBorderColor,
	borderTopLeftRadius: '4px',
	borderTopRightRadius: '4px'
}, {
	hover: {
		backgroundColor: hoverColor,
		backgroundImage: hoverGradient
	},
	active: {
		backgroundImage: activeGradient
	}
});

cssList.addClass('lx-MultiBox-active', 'background-image:'+activeGradient);
/* MultiBox */
/*============================================================================*/


/*============================================================================*/
/* Paginator */
cssList.addClass('lx-Paginator', {
	border: 'solid 1px #d9d9d9',
	borderRadius: '8px'
});

cssList.addClass('lx-Paginator-middle', {
	width: 'auto'
});

cssList.addClass('lx-Paginator-active', {
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%',
	backgroundImage: 'url(img/pgnActive.jpg)',
	borderRadius: '8px'
});

cssList.addClass('lx-Paginator-page', {
	cursor: 'pointer'
});

cssList.addAbstractClass('lx-Paginator-button', {
	cursor: 'pointer',
	// width: '40px',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%'
});
cssList.inheritClass('lx-Paginator-to-start', 'lx-Paginator-button',
	'background-image: url(img/pgnToStart.jpg)',
	{disabled: 'cursor:default;background-image: url(img/pgnToStartD.jpg)'}
);
cssList.inheritClass('lx-Paginator-to-finish', 'lx-Paginator-button',
	'background-image: url(img/pgnToFinish.jpg)',
	{disabled: 'cursor:default;background-image: url(img/pgnToFinishD.jpg)'}
);
cssList.inheritClass('lx-Paginator-to-left', 'lx-Paginator-button',
	'background-image: url(img/pgnToLeft.jpg)',
	{disabled: 'cursor:default;background-image: url(img/pgnToLeftD.jpg)'}
);
cssList.inheritClass('lx-Paginator-to-right', 'lx-Paginator-button',
	'background-image: url(img/pgnToRight.jpg)',
	{disabled: 'cursor:default;background-image: url(img/pgnToRightD.jpg)'}
);
/* Paginator */
/*============================================================================*/


/*============================================================================*/
/* TableManager */
cssList.addClasses({
	'lx-TM-table': 'border: #00CC00 solid 2px',
	'lx-TM-row': 'background-color: #FFFF99',
	'lx-TM-cell': 'background-color: #AAFFAA !important'
});
/* TableManager */
/*============================================================================*/

cssList.addStyle('@font-face', {
	fontFamily: 'TestFont',
	src: 'url("font/Muli-VariableFont_wght.ttf") format("truetype")',
	fontStyle: 'normal',
	fontWeight: 'normal'
});

return cssList.toString();
