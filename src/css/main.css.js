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







/*
TODO - нужны функции преобразования цветов по аналогии с LESS

var aldenTurquoise = '#1C8382';
var aldenRed = '#DC2E2F';
var buttonBorderColor = '#0f4443';
var intactGradient = 'linear-gradient(to bottom, #25adac, #135958)';
var activeGradient = 'linear-gradient(to bottom, #e35a5a, #b81f20)';

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




#lx:require colorSchema/white;
// #lx:require colorSchema/dark;




var borderRadius = '5px';

var iconFlex = {
	display: 'flex',
	flexDirection: 'row',
	alignItems: 'center',
	justifyContent: 'center',
	color: widgetIconColor
};

var iconStyle = {
	fontSize: 'calc(30px + 1.0vh)',
	fontWeight: '500',
	paddingBottom: '6px',
	color: 'inherit',
	fontFamily: 'MainFont',
};

function icon(code, config = null) {
	var style = iconStyle.lxClone();
	if (config) {
		if (config.isNumber) style.fontSize = config;
		else if (config.isObject) style = style.lxMerge(config, true);
		if (style.fontSize.isNumber)
			style.fontSize = 'calc('+style.fontSize+'px + 1.0vh)';
	}
	return style.lxMerge({content: "'" + code + "'"});
}










var cssList = new lx.CssContext();


cssList.addClass('lxbody', {
	position: 'absolute',
	left: '0%',
	top: '0%',
	width: '100%',
	height: '100%',
	overflow: 'auto',
	backgroundColor: mainBackgroundColor
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


cssList.addStyle('input', {
	overflow: 'hidden',
	visibility: 'inherit',
	boxSizing: 'border-box'
});

cssList.addStyle('div', {
	overflow: 'visible',
	visibility: 'inherit',
	boxSizing: 'border-box',
	color: textColor
});


/*============================================================================*/
/* TextBox */
cssList.addClass('lx-TextBox', {
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
var butShadowSize = Math.floor(shadowSize * 0.33) + 3;
var butShadowShift = Math.floor(butShadowSize * 0.5);

cssList.addAbstractClass('Button', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	borderRadius: borderRadius,
	boxShadow: '0 '+butShadowShift+'px '+butShadowSize+'px rgba(0,0,0,0.5)',
	cursor: 'pointer',
	backgroundColor: widgetBackgroundColor,
});

cssList.inheritAbstractClass('ActiveButton', 'Button', {
	marginTop: '0px',
}, {
	'hover:not([disabled])': {
		marginTop: '-2px',
		boxShadow: '0 '+(Math.round(butShadowShift*1.5))+'px '+(Math.round(butShadowSize*1.5))+'px rgba(0,0,0,0.5)',
		// boxShadow: '0 3px 8px rgba(0,0,0,0.5)',
		transition: 'margin-top 0.1s linear, box-shadow 0.1s linear',
	},
	'active:not([disabled])': {
		marginTop: '0px',
		boxShadow: '0 '+butShadowShift+'px '+butShadowSize+'px rgba(0,0,0,0.5)',
		transition: 'margin-top 0.05s linear, box-shadow 0.05s linear'
	},
	disabled: {
		opacity: '0.5',
		cursor: 'default'
	}
});

cssList.inheritClass('lx-Button', 'ActiveButton');
/* Button */
/*============================================================================*/


/*============================================================================*/
/* ActiveBox */
var abShadowSize = shadowSize + 2;
var abShadowShift = Math.floor(abShadowSize * 0.5);

cssList.addClass('lx-ActiveBox', {
	overflow: 'hidden',
	borderRadius: borderRadius,
	boxShadow: '0 '+abShadowShift+'px '+abShadowSize+'px rgba(0,0,0,0.5)',
	minWidth: '50px',
	minHeight: '75px',
	backgroundColor: bodyBackgroundColor
});

cssList.addClass('lx-ActiveBox-header', {
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	cursor: 'move',
	borderRadius: borderRadius,
	boxShadow: '0 0px 3px rgba(0,0,0,0.5) inset',
	background: widgetGradient
});

cssList.addClass('lx-ActiveBox-close', {
	cursor: 'pointer'
}.lxMerge(iconFlex), {
	after: icon('\\2715', {fontSize:10, paddingBottom:'3px'})
});


cssList.addStyle('.lx-ActiveBox-header .lx-TextBox', {
	fontWeight: 'bold',
	color: headerTextColor
});

cssList.addClass('lx-ActiveBox-body', {
	overflow: 'auto',
	backgroundColor: altBodyBackgroundColor
});

cssList.addClass('lx-ActiveBox-resizer', {
	cursor: 'se-resize',
	borderRadius: borderRadius,
	backgroundColor: bodyBackgroundColor
}.lxMerge(iconFlex), {
	after: icon('\\21F2', {fontSize:10, paddingBottom:'0px'})
});

cssList.addClass('lx-ActiveBox-move', {
	marginTop: '-2px',
	boxShadow: '0 '+(Math.round(abShadowShift*1.5))+'px '+(Math.round(abShadowSize*1.5))+'px rgba(0,0,0,0.5)',
});
/* ActiveBox */
/*============================================================================*/


/*============================================================================*/
/* MultiBox */
cssList.addClass('lx-MultiBox', {
	borderRadius: borderRadius,
	boxShadow: '0 0px '+shadowSize+'px rgba(0,0,0,0.5)',
	backgroundColor: bodyBackgroundColor
});

cssList.inheritClass('lx-MultiBox-mark', 'ActiveButton', {
	backgroundColor: coldSoftColor,
	color: coldDeepColor
});

cssList.addClass('lx-MultiBox-active', {
	backgroundColor: coldDeepColor,
	color: coldSoftColor
});
/* MultiBox */
/*============================================================================*/


/*============================================================================*/
/* Input */
cssList.addAbstractClass('Input', {
	border: '1px solid ' + widgetBorderColor,
	padding: '4px 5px',
	background: textBackgroundColor,
	borderRadius: borderRadius,
	outline: 'none',
	boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.3)',
	fontFamily: 'MainFont',
	fontSize: 'calc(10px + 1.0vh)',
	color: textColor
});

cssList.inheritClass('lx-Input', 'Input', {
}, {
	focus: 'border: 1px solid ' + checkedMainColor,
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
	borderRadius: borderRadius,
	cursor: 'pointer',
	overflow: 'hidden'
}, {
	disabled: 'opacity: 0.5'
});

cssList.addClass('lx-Dropbox-input', {
	position: 'absolute',
	width: 'calc(100% - 30px)',
	height: '100%',
	borderTopRightRadius: 0,
	borderBottomRightRadius: 0
});

cssList.addClass('lx-Dropbox-but', {
	position: 'absolute',
	right: 0,
	width: '30px',
	height: '100%',
	borderTop: '1px solid ' + widgetBorderColor,
	borderBottom: '1px solid ' + widgetBorderColor,
	borderRight: '1px solid ' + widgetBorderColor,
	background: widgetGradient,
	cursor: 'pointer'
}.lxMerge(iconFlex), {
	hover: 'opacity: 0.7',
	after: icon('\\25BC', 15)
});

cssList.addClass('lx-Dropbox-cell', {
}, {
	hover: 'background-color:' + checkedSoftColor
});
/* Dropbox */
/*============================================================================*/


/*============================================================================*/
/* Slider */
cssList.inheritClass('lx-slider-track', 'Button');
cssList.inheritClass('lx-slider-handle', 'ActiveButton');
/* Slider */
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
});
/* LabeledGroup */
/*============================================================================*/


/*============================================================================*/
/* Paginator */
cssList.addClass('lx-Paginator', {
	gridTemplateRows: '100%',
	overflow: 'hidden',
	whiteSpace: 'nowrap',
	textOverflow: 'ellipsis',
	border: 'solid 1px ' + widgetBorderColor,
	borderRadius: borderRadius
});

cssList.addClass('lx-Paginator-middle', {
	width: 'auto'
});

cssList.addClass('lx-Paginator-page', {
	cursor: 'pointer'
});

cssList.addAbstractClass('Paginator-button', {
	background: widgetGradient,
	cursor: 'pointer'
}.lxMerge(iconFlex), {
	disabled: {
		color: widgetIconColorDisabled,
		cursor: 'default'
	}
});
cssList.inheritClass('lx-Paginator-active', 'Paginator-button', { borderRadius: borderRadius });
cssList.inheritClass('lx-Paginator-to-finish', 'Paginator-button', {}, {after: icon('\\00BB')});
cssList.inheritClass('lx-Paginator-to-start',  'Paginator-button', {}, {after: icon('\\00AB')});
cssList.inheritClass('lx-Paginator-to-left',   'Paginator-button', {}, {after: icon('\\2039')});
cssList.inheritClass('lx-Paginator-to-right',  'Paginator-button', {}, {after: icon('\\203A')});
/* Paginator */
/*============================================================================*/


/*============================================================================*/
/* BoxSlider */
cssList.addAbstractClass('lx-IS-button', {
	backgroundImage: 'url(img/ISarroy.png)',
	backgroundRepeat: 'no-repeat',
	backgroundSize: '100% 100%',
	borderTopLeftRadius: borderRadius,
	borderBottomLeftRadius: borderRadius,
	opacity: '0.3'
});

cssList.inheritClass('lx-IS-button-l', 'lx-IS-button', {
	transform: 'rotate(180deg)',
}, {
	hover: 'background-color: black'
});

cssList.inheritClass('lx-IS-button-r', 'lx-IS-button', {
}, {
	hover: 'background-color: black'
});
/* BoxSlider */
/*============================================================================*/


/*============================================================================*/
/* Table */
var tableBorderColor = '#D8D8D8';

cssList.addClass('lx-Table', {
	border: '1px solid ' + tableBorderColor,
	borderRadius: borderRadius
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
/* TreeBox */
cssList.addClass('lx-TreeBox', {
	backgroundColor: bodyBackgroundColor,
	borderRadius: '10px'
});

cssList.inheritAbstractClass('lx-TW-Button', 'ActiveButton', {
	backgroundColor: checkedSoftColor,
	color: 'black'
}.lxMerge(iconFlex));
cssList.inheritClass('lx-TW-Button-closed', 'lx-TW-Button', {}, {after: icon('\\25BA', {
	fontSize: 10,
	paddingBottom: '3px',
	paddingLeft: '2px'
})});
cssList.inheritClass('lx-TW-Button-opened', 'lx-TW-Button', {}, {after: icon('\\25BC', {
	fontSize:10,
	paddingBottom:'2px'
})});
cssList.inheritClass('lx-TW-Button-add', 'lx-TW-Button', {}, {after: icon('\\002B', {
	fontSize:12,
	fontWeight: 700,
	paddingBottom:'3px'
})});
cssList.inheritClass('lx-TW-Button-del', 'lx-TW-Button', {}, {after: icon('\\002D', {
	fontSize:12,
	fontWeight: 700,
	paddingBottom:'3px'
})});

cssList.inheritClass('lx-TW-Button-empty', 'Button', {
	backgroundColor: checkedSoftColor,
	cursor: 'default'	
});

cssList.addClass('lx-TW-Label', {
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
	borderBottomLeftRadius: borderRadius,
	borderBottomRightRadius: borderRadius,
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
/* TableManager */
cssList.addClasses({
	'lx-TM-table': 'border: #00CC00 solid 2px',
	'lx-TM-row': 'background-color: #FFFF99',
	'lx-TM-cell': 'background-color: #AAFFAA !important'
});
/* TableManager */
/*============================================================================*/


cssList.addStyle('@font-face', {
	fontFamily: 'MainFont',
	src: 'url("font/Muli-VariableFont_wght.ttf") format("truetype")',
	fontStyle: 'normal',
	fontWeight: 600
});


return cssList.toString();
