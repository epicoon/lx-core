#lx:module lx.MainCssContext;

#lx:use lx.CssColorSchema;

class MainCssContext #lx:namespace lx {
    #lx:const
        borderRadius = '5px',
        butShadowSize = Math.floor(lx.CssColorSchema.shadowSize * 0.33) + 3,
        butShadowShift = Math.floor(this.butShadowSize * 0.5);
    
    static get cssContext() {
        if (this.__css === undefined) {
            this.__css = new lx.CssContext();
            __init(this.__css);
        }
        
        return this.__css;
    }
}

function __init(cssContext) {
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
            if (lx.isNumber(config)) iconStyle.fontSize = config;
            else if (lx.isObject(config)) iconStyle = iconStyle.lxMerge(config, true);
            if (lx.isNumber(iconStyle.fontSize))
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
        borderRadius: lx.MainCssContext.borderRadius,
        boxShadow: '0 ' + lx.MainCssContext.butShadowShift + 'px '
            + lx.MainCssContext.butShadowSize + 'px rgba(0,0,0,0.5)',
        cursor: 'pointer',
        color: lx.CssColorSchema.textColor,
        backgroundColor: lx.CssColorSchema.widgetBackgroundColor,
    });

    cssContext.addAbstractClass('Input', {
        border: '1px solid ' + lx.CssColorSchema.widgetBorderColor,
        padding: '4px 5px',
        background: lx.CssColorSchema.textBackgroundColor,
        borderRadius: lx.MainCssContext.borderRadius,
        outline: 'none',
        boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.3)',
        fontFamily: 'MainFont',
        fontSize: 'calc(10px + 1.0vh)',
        color: lx.CssColorSchema.textColor
    });

    cssContext.inheritAbstractClass('ActiveButton', 'Button', {
        marginTop: '0px',
    }, {
        'hover:not([disabled])': {
            marginTop: '-2px',
            boxShadow: '0 '+(Math.round(lx.MainCssContext.butShadowShift * 1.5)) + 'px '
                + (Math.round(lx.MainCssContext.butShadowSize * 1.5)) + 'px rgba(0,0,0,0.5)',
            // boxShadow: '0 3px 8px rgba(0,0,0,0.5)',
            transition: 'margin-top 0.1s linear, box-shadow 0.1s linear',
        },
        'active:not([disabled])': {
            marginTop: '0px',
            boxShadow: '0 ' + lx.MainCssContext.butShadowShift + 'px '
                + lx.MainCssContext.butShadowSize + 'px rgba(0,0,0,0.5)',
            transition: 'margin-top 0.05s linear, box-shadow 0.05s linear'
        },
        disabled: {
            opacity: '0.5',
            cursor: 'default'
        }
    });
}
