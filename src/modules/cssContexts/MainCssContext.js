#lx:module lx.MainCssContext;

#lx:require @core/js/classes/css/CssContext;

var borderRadius = '5px';
var butShadowSize = Math.floor(shadowSize * 0.33) + 3;
var butShadowShift = Math.floor(butShadowSize * 0.5);

var cssContext = new lx.CssContext();

cssContext.registerMixin('icon', (iconCode, config=null)=>{
    var iconFlex = {
        display: 'flex',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center'
    };
    var iconStyle = {
        fontSize: 'calc(30px + 1.0vh)',
        fontWeight: '500',
        paddingBottom: '6px',
        color: 'inherit',
        fontFamily: 'MainFont',
    };
    if (config) {
        if (config.isNumber) iconStyle.fontSize = config;
        else if (config.isObject) iconStyle = iconStyle.lxMerge(config, true);
        if (iconStyle.fontSize.isNumber)
            iconStyle.fontSize = 'calc('+iconStyle.fontSize+'px + 1.0vh)';
    }
    iconStyle.lxMerge({content: "'" + iconCode + "'"});
    return {
        content: iconFlex,
        pseudoclasses: {
            after: iconStyle
        }
    };
});

cssContext.addAbstractClass('Button', {
    overflow: 'hidden',
    whiteSpace: 'nowrap',
    textOverflow: 'ellipsis',
    borderRadius: borderRadius,
    boxShadow: '0 '+butShadowShift+'px '+butShadowSize+'px rgba(0,0,0,0.5)',
    cursor: 'pointer',
    backgroundColor: widgetBackgroundColor,
});

cssContext.addAbstractClass('Input', {
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

cssContext.inheritAbstractClass('ActiveButton', 'Button', {
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
