#lx:use #lx:php(\lx::$app->getConfig('colorSchema'));
#lx:use lx.MainCssContext;


/*============================================================================*/
cssContext.addClass('lxbody', {
	position: 'absolute',
	left: '0%',
	top: '0%',
	width: '100%',
	height: '100%',
	overflow: 'hidden',
	backgroundColor: mainBackgroundColor
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

cssContext.addStyle('input', {
	overflow: 'hidden',
	visibility: 'inherit',
	boxSizing: 'border-box'
});

cssContext.addStyle('div', {
	overflow: 'visible',
	visibility: 'inherit',
	boxSizing: 'border-box',
	color: textColor
});

cssContext.addStyle('@font-face', {
	fontFamily: 'MainFont',
	src: 'url("font/Muli-VariableFont_wght.ttf") format("truetype")',
	fontStyle: 'normal',
	fontWeight: 600
});

cssContext.addClass('lx-Box', {
	borderRadius: borderRadius,
	boxShadow: '0 0px '+shadowSize+'px rgba(0,0,0,0.5)',
	backgroundColor: bodyBackgroundColor
});
/*============================================================================*/


/*============================================================================*/
/* TextBox */
/* Button */
/* ActiveBox */
/* MultiBox */
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
/* Paginator */
/* TableManager */
/* EggMenu */
/*============================================================================*/


/*============================================================================*/
/* TextBox */
cssContext.addClass('lx-TextBox', {
	padding: '0px 5px',
	width: 'auto',
	height: 'auto',
	fontFamily: 'MainFont',
	fontSize: 'calc(10px + 1.0vh)',

	color: 'inherit',
	cursor: 'inherit',
	overflow: 'inherit',
	whiteSpace: 'inherit',
	textOverflow: 'inherit',
});
/* TextBox */
/*============================================================================*/


/*============================================================================*/
/* Button */
cssContext.inheritClass('lx-Button', 'ActiveButton');
/* Button */
/*============================================================================*/


/*============================================================================*/
/* ActiveBox */
var abShadowSize = shadowSize + 2;
var abShadowShift = Math.floor(abShadowSize * 0.5);

cssContext.addClass('lx-ActiveBox', {
	overflow: 'hidden',
	borderRadius: borderRadius,
	boxShadow: '0 '+abShadowShift+'px '+abShadowSize+'px rgba(0,0,0,0.5)',
	minWidth: '50px',
	minHeight: '75px',
	backgroundColor: bodyBackgroundColor
});

cssContext.addClass('lx-ActiveBox-header', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	cursor: 'move',
	borderRadius: borderRadius,
	boxShadow: '0 0px 3px rgba(0,0,0,0.5) inset',
	background: widgetGradient
});

cssContext.addClass('lx-ActiveBox-close', {
	cursor: 'pointer',
	color: widgetIconColor,
	'@icon': ['\\2715', {fontSize:10, paddingBottom:'3px'}]
});


cssContext.addStyle('.lx-ActiveBox-header .lx-TextBox', {
	fontWeight: 'bold',
	color: headerTextColor
});

cssContext.addClass('lx-ActiveBox-body', {
	overflow: 'auto',
	backgroundColor: altBodyBackgroundColor
});

cssContext.addClass('lx-ActiveBox-resizer', {
	cursor: 'se-resize',
	borderRadius: borderRadius,
	color: widgetIconColor,
	backgroundColor: bodyBackgroundColor,
	'@icon': ['\\21F2', {fontSize:10, paddingBottom:'0px'}]
});

cssContext.addClass('lx-ActiveBox-move', {
	marginTop: '-2px',
	boxShadow: '0 '+(Math.round(abShadowShift*1.5))+'px '+(Math.round(abShadowSize*1.5))+'px rgba(0,0,0,0.5)',
});
/* ActiveBox */
/*============================================================================*/


/*============================================================================*/
/* MultiBox */
cssContext.inheritClass('lx-MultiBox', 'lx-Box', {});

cssContext.inheritClass('lx-MultiBox-mark', 'ActiveButton', {
	backgroundColor: coldSoftColor,
	color: coldDeepColor
});

cssContext.addClass('lx-MultiBox-active', {
	backgroundColor: coldDeepColor,
	color: coldSoftColor
});
/* MultiBox */
/*============================================================================*/


/*============================================================================*/
/* Input */
cssContext.inheritClass('lx-Input', 'Input', {
}, {
	focus: 'border: 1px solid ' + checkedMainColor,
	disabled: 'opacity: 0.5'
});
/* Input */
/*============================================================================*/


/*============================================================================*/
/* Textarea */
cssContext.inheritClass('lx-Textarea', 'Input', {
	resize: 'none'
});
/* Textarea */
/*============================================================================*/


/*============================================================================*/
/* Dropbox */
cssContext.addClass('lx-Dropbox', {
	borderRadius: borderRadius,
	cursor: 'pointer',
	overflow: 'hidden'
}, {
	disabled: 'opacity: 0.5'
});

cssContext.addClass('lx-Dropbox-input', {
	position: 'absolute',
	width: 'calc(100% - 30px)',
	height: '100%',
	borderTopRightRadius: 0,
	borderBottomRightRadius: 0
});

cssContext.addClass('lx-Dropbox-but', {
	position: 'absolute',
	right: 0,
	width: '30px',
	height: '100%',
	borderTop: '1px solid ' + widgetBorderColor,
	borderBottom: '1px solid ' + widgetBorderColor,
	borderRight: '1px solid ' + widgetBorderColor,
	color: widgetIconColor,
	background: widgetGradient,
	cursor: 'pointer',
	'@icon': ['\\25BC', 15]
});

cssContext.addClass('lx-Dropbox-cell', {
}, {
	hover: 'background-color:' + checkedSoftColor
});
/* Dropbox */
/*============================================================================*/


/*============================================================================*/
/* Slider */
cssContext.inheritClass('lx-slider-track', 'Button');
cssContext.inheritClass('lx-slider-handle', 'ActiveButton');
/* Slider */
/*============================================================================*/


/*============================================================================*/
/* Checkbox */
var checkboxSprite = 'url(img/crsprite.png)';

cssContext.addAbstractClass('Checkbox-shape', {
	width: '23px !important',
	height: '23px !important',
	backgroundImage: checkboxSprite,
	cursor: 'pointer'
});

cssContext.inheritClass('lx-Checkbox-0', 'Checkbox-shape', {
	backgroundPosition: '-2px -3px'
}, {
	hover: 'background-position: -46px -3px',
	active: 'background-position: -70px -3px',
	disabled: 'background-position: -184px -3px'
});

cssContext.inheritClass('lx-Checkbox-1', 'Checkbox-shape', {
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
cssContext.inheritClass('lx-Radio-0', 'Checkbox-shape', {
	backgroundPosition: '-1px -24px'
}, {
	hover: 'background-position: -45px -24px',
	active: 'background-position: -69px -24px',
	disabled: 'background-position: -184px -24px'
});

cssContext.inheritClass('lx-Radio-1', 'Checkbox-shape', {
	backgroundPosition: '-91px -24px'
}, {
	hover: 'background-position: -135px -24px',
	active: 'background-position: -160px -24px',
	disabled: 'background-position: -206px -24px'
});
/* Radio */
/*============================================================================*/


/*============================================================================*/
/* LabeledGroup */
cssContext.addClass('lx-LabeledGroup', {
	display: 'grid',
	gridAutoFlow: 'row',
	gridGap: '.8em',
	padding: '1.2em'
});

cssContext.addClass('lx-LabeledGroup-item', {
	position: 'relative',
	gridRow: 'auto'
});

cssContext.addClass('lx-LabeledGroup-label', {
});
/* LabeledGroup */
/*============================================================================*/


/*============================================================================*/
/* Paginator */
cssContext.addClass('lx-Paginator', {
	gridTemplateRows: '100%',
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	border: 'solid 1px ' + widgetBorderColor,
	borderRadius: borderRadius
});

cssContext.addClass('lx-Paginator-middle', {
	width: 'auto'
});

cssContext.addClass('lx-Paginator-page', {
	cursor: 'pointer'
});

cssContext.addAbstractClass('Paginator-button', {
	background: widgetGradient,
	color: widgetIconColor,
	cursor: 'pointer'
});
cssContext.inheritClass('lx-Paginator-active', 'Paginator-button', { borderRadius: borderRadius });
cssContext.inheritClasses({
	'lx-Paginator-to-finish': { '@icon': '\\00BB' },
	'lx-Paginator-to-start' : { '@icon': '\\00AB' },
	'lx-Paginator-to-left'  : { '@icon': '\\2039' },
	'lx-Paginator-to-right' : { '@icon': '\\203A' }
}, 'Paginator-button');
/* Paginator */
/*============================================================================*/


/*============================================================================*/
/* BoxSlider */
cssContext.addAbstractClass('lx-IS-button', {
	backgroundImage: 'url(img/ISarroy.png)',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%',
	borderTopLeftRadius: borderRadius,
	borderBottomLeftRadius: borderRadius,
	opacity: '0.3'
});

cssContext.inheritClass('lx-IS-button-l', 'lx-IS-button', {
	transform: 'rotate(180deg)',
}, {
	hover: 'background-color: black'
});

cssContext.inheritClass('lx-IS-button-r', 'lx-IS-button', {
}, {
	hover: 'background-color: black'
});
/* BoxSlider */
/*============================================================================*/


/*============================================================================*/
/* Table */
var tableBorderColor = '#D8D8D8';

cssContext.addClass('lx-Table', {
	border: '1px solid ' + tableBorderColor,
	borderRadius: borderRadius
});

cssContext.addClass('lx-Table-row', {
	borderTop: '1px solid ' + tableBorderColor
}, {
	'first-child': 'border: 0px',
	'nth-child(2n)': 'background-color: #F6F6F6',
	'nth-child(2n+1)': 'background-color: white'
});

cssContext.addClass('lx-Table-cell', {
	height: '100%',
	borderRight: '1px solid ' + tableBorderColor
}, {
	'last-child': 'border: 0px'
});

//TODO - после переделки lx.Box.entry это не надо будет
cssContext.addStyle('.lx-Table-cell .lx-Textarea', {
	borderRadius: '0px'
});
/* Table */
/*============================================================================*/


/*============================================================================*/
/* TreeBox */
cssContext.addClass('lx-TreeBox', {	
	backgroundColor: altBodyBackgroundColor,
	borderRadius: '10px'
});

cssContext.inheritAbstractClass('lx-TW-Button', 'ActiveButton', {
	color: widgetIconColor,
	backgroundColor: checkedSoftColor
});
cssContext.inheritClasses({
	'lx-TW-Button-closed': { '@icon': ['\\25BA', {fontSize:10, paddingBottom:'3px', paddingLeft:'2px'}] },
	'lx-TW-Button-opened': { '@icon': ['\\25BC', {fontSize:10, paddingBottom:'2px'}] },
	'lx-TW-Button-add'   : { '@icon': ['\\002B', {fontSize:12, paddingBottom:'3px', fontWeight: 700}] },
	'lx-TW-Button-del'   : { '@icon': ['\\002D', {fontSize:12, paddingBottom:'3px', fontWeight: 700}] }
}, 'lx-TW-Button');

cssContext.inheritClass('lx-TW-Button-empty', 'Button', {
	backgroundColor: checkedSoftColor,
	cursor: 'default'	
});

cssContext.addClass('lx-TW-Label', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	backgroundColor: textBackgroundColor,
	borderRadius: borderRadius
});
/* TreeBox */
/*============================================================================*/


/*============================================================================*/
/* Calendar */
var calendarSideBackground = 'linear-gradient(to bottom, #9AD9F7, #146FBB)';
var calendarSideTextColor = 'white';

cssContext.inheritClass('lx-Calendar', 'lx-Input');

cssContext.addClass('lx-Calendar-arroy', {
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

cssContext.addClass('lx-Calendar-day-of-week', {
	backgroundImage: calendarSideBackground,
	color: calendarSideTextColor,
	fontWeight: 'bold'
});

cssContext.inheritClass('lx-Calendar-today', 'lx-Calendar-day-of-week', {
	borderBottomLeftRadius: borderRadius,
	borderBottomRightRadius: borderRadius,
	cursor: 'pointer'
});

cssContext.inheritClass('lx-Calendar-cell-today', 'lx-Calendar-day-of-week', {
	cursor: 'pointer'
});

cssContext.addClass('lx-Calendar-cell-day', {
	cursor: 'pointer'
});

cssContext.addClass('lx-Calendar-menu');
cssContext.addStyleGroup('.lx-Calendar-menu', {
	'.lx-Table' : 'border-radius: 0px',
	'.lx-Calendar-cell-day:hover': 'font-weight: bold'
});
/* Calendar */
/*============================================================================*/


/*============================================================================*/
/* TableManager */
cssContext.addClasses({
	'lx-TM-table': 'border: #00CC00 solid 2px',
	'lx-TM-row': 'background-color: #FFFF99',
	'lx-TM-cell': 'background-color: #AAFFAA !important'
});
/* TableManager */
/*============================================================================*/


/*============================================================================*/
/* EggMenu */
cssContext.addClass('lx-EggMenu', {
	overflow: 'visible',
	borderRadius: '25px',
	boxShadow: '0 '+abShadowShift+'px '+abShadowSize+'px rgba(0,0,0,0.5)'
});

cssContext.addClass('lx-EggMenu-top', {
	backgroundColor: bodyBackgroundColor,
	borderTopLeftRadius: '25px',
	borderTopRightRadius: '25px'
});

cssContext.addClass('lx-EggMenu-bottom', {
	backgroundColor: checkedSoftColor,
	borderBottomLeftRadius: '25px',
	borderBottomRightRadius: '25px'
});

cssContext.addClass('lx-EggMenu-move', {
	marginTop: '-2px',
	boxShadow: '0 '+(Math.round(abShadowShift*1.5))+'px '+(Math.round(abShadowSize*1.5))+'px rgba(0,0,0,0.5)'
});
/* EggMenu */
/*============================================================================*/

return cssContext.toString();
