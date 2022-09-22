#lx:module lx.SourceCssContext;

#lx:namespace lx;
class SourceCssContext extends lx.CssContext {
    init(cssPreset) {
        this.addClass('lxbody', {
            position: 'absolute',
            left: '0%',
            top: '0%',
            width: '100%',
            height: '100%',
            overflow: 'hidden',
            fontFamily: 'DejaVu Sans Mono, monospace',
            fontSize: 'calc(10px + 1.0vh)',
            color: cssPreset.textColor,
            backgroundColor: cssPreset.mainBackgroundColor
        });

        this.addClass('lx-abspos', {
            position: 'absolute'
        });

        this.addClass('lxps-grid-v', {
            display: 'grid',
            gridAutoFlow: 'row',
            gridTemplateColumns: '1fr',
            gridAutoRows: 'auto'
        });
        this.addClass('lxps-grid-h', {
            display: 'grid',
            gridAutoFlow: 'column',
            gridTemplateRows: '1fr',
            gridAutoColumns: 'auto'
        });

        this.addStyle('input', {
            overflow: 'hidden',
            visibility: 'inherit',
            boxSizing: 'border-box'
        });

        this.addStyle('div', {
            overflow: 'visible',
            visibility: 'inherit',
            boxSizing: 'border-box',
            color: 'inherit'
        });
    }
}
