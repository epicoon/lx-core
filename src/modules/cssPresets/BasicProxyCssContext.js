#lx:module lx.BasicProxyCssContext;

#lx:namespace lx;
class BasicProxyCssContext extends lx.CssContext {
    init(cssPreset) {
        let butShadowSize = Math.floor(cssPreset.shadowSize * 0.33) + 3;
        let butShadowShift = Math.floor(butShadowSize * 0.5);

        this.registerMixin('img', url => {
            return {
                backgroundImage: 'url(' + url + ')',
                backgroundRepeat: 'no-repeat',
                backgroundSize: '100% 100%'
            };
        });

        this.registerMixin('ellipsis', ()=>{
            return {
                overflow: 'hidden',
                whiteSpace: 'nowrap',
                textOverflow: 'ellipsis'
            }
        });

        this.registerMixin('icon', (iconCode, config = null) => {
            var iconFlex = {
                display: 'flex',
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'center'
            };
            var iconStyle = {
                fontSize: 'calc(30px + 1.0vh)',
                fontWeight: '500',
                color: 'inherit',
                fontFamily: 'MainFont',
                content: "'" + iconCode + "'"
            };
            if (config) {
                if (lx.isNumber(config)) iconStyle.fontSize = config;
                else if (lx.isObject(config)) iconStyle = iconStyle.lxMerge(config, true);
                if (lx.isNumber(iconStyle.fontSize))
                    iconStyle.fontSize = 'calc(' + iconStyle.fontSize + 'px + 1.0vh)';
            }
            return {
                content: iconFlex,
                pseudoclasses: {
                    after: iconStyle
                }
            };
        });

        this.registerMixin('clickable', () => {
            return {
                content: {
                    marginTop: '0px',
                    cursor: 'pointer',
                    boxShadow: '0 ' + butShadowShift + 'px ' + butShadowSize + 'px rgba(0,0,0,0.5)',
                },
                pseudoclasses: {
                    'hover:not([disabled])': {
                        marginTop: '-2px',
                        boxShadow: '0 ' + (Math.round(butShadowShift * 1.5)) + 'px '
                            + (Math.round(butShadowSize * 1.5)) + 'px rgba(0,0,0,0.5)',
                        transition: 'margin-top 0.1s linear, box-shadow 0.1s linear',
                    },
                    'active:not([disabled])': {
                        marginTop: '0px',
                        boxShadow: '0 ' + butShadowShift + 'px ' + butShadowSize + 'px rgba(0,0,0,0.5)',
                        transition: 'margin-top 0.08s linear, box-shadow 0.05s linear'
                    },
                }
            };
        });

        this.addAbstractClass('AbstractBox', {
            borderRadius: cssPreset.borderRadius,
            boxShadow: '0 0px ' + cssPreset.shadowSize + 'px rgba(0,0,0,0.5)',
            backgroundColor: cssPreset.bodyBackgroundColor
        });

        this.addAbstractClass('Button', {
            overflow: 'hidden',
            whiteSpace: 'nowrap',
            textOverflow: 'ellipsis',
            borderRadius: cssPreset.borderRadius,
            boxShadow: '0 ' + butShadowShift + 'px '
                + butShadowSize + 'px rgba(0,0,0,0.5)',
            cursor: 'pointer',
            color: cssPreset.textColor,
            backgroundColor: cssPreset.widgetBackgroundColor,
        });

        this.addAbstractClass('Input', {
            border: '1px solid ' + cssPreset.widgetBorderColor,
            padding: '4px 5px',
            background: cssPreset.textBackgroundColor,
            borderRadius: cssPreset.borderRadius,
            outline: 'none',
            boxShadow: 'inset 0 1px 2px rgba(0, 0, 0, 0.3)',
            fontFamily: 'MainFont',
            fontSize: 'calc(10px + 1.0vh)',
            color: cssPreset.textColor
        });

        this.inheritAbstractClass('ActiveButton', 'Button', {
            '@clickable': true
        }, {
            disabled: {
                opacity: '0.5',
                cursor: 'default'
            }
        });
    }
}
