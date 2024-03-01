#lx:module lx.Calculator;

#lx:use lx.Box;
#lx:use lx.Input;
#lx:use lx.Button;

/**
 * @widget lx.Calculator
 * @content-disallowed
 */
#lx:namespace lx;
class Calculator extends lx.Box {
    getBasicCss() {
        return {
            actionBut: 'lx-calc-abut',
            clearBut: 'lx-calc-cbut',
            numberBut: 'lx-calc-nbut',
            resultBut: 'lx-calc-rbut'
        };
    }

    static initCss(css) {
        css.addClass('lx-calc-nbut', {
            backgroundColor: css.preset.textBackgroundColor
        });
        css.addClass('lx-calc-cbut', {
            backgroundColor: css.preset.coldDeepColor
        });
        css.addClass('lx-calc-abut', {
        });
        css.addClass('lx-calc-rbut', {
            backgroundColor: css.preset.checkedDeepColor
        });
    }

    /**
     * @widget-init
     *
     * @param [config] {Object: {
     *     #merge(lx.Rect::constructor::config),
     *     [grid] {Object: #schema(lx.GridPositioningStrategy::applyConfig::config)}
     * }}
     */
    render(config) {
        super.render(config);

        let gridConfig = config.grid || {
            indent: '10px'
        };
        gridConfig.cols = 4;
        this.gridProportional(gridConfig);

        this.add(lx.Input, {key:'input', width: 4});
        let buts = [
            ['(', this.basicCss.actionBut], [')', this.basicCss.actionBut],
            ['<', this.basicCss.clearBut],  ['CE', this.basicCss.clearBut],
            ['7', this.basicCss.numberBut], ['8', this.basicCss.numberBut],
            ['9', this.basicCss.numberBut], ['+', this.basicCss.actionBut],
            ['4', this.basicCss.numberBut], ['5', this.basicCss.numberBut],
            ['6', this.basicCss.numberBut], ['-', this.basicCss.actionBut],
            ['1', this.basicCss.numberBut], ['2', this.basicCss.numberBut],
            ['3', this.basicCss.numberBut], ['*', this.basicCss.actionBut],
            ['0', this.basicCss.numberBut], ['.', this.basicCss.numberBut],
            ['=', this.basicCss.resultBut], ['/', this.basicCss.actionBut]
        ];
        for (let i in buts) {
            let text = buts[i][0],
                css = buts[i][1];
            this.add(lx.Button, {key:'but', text, css});
        }
    }

    #lx:client {
        clientRender(config) {
            super.clientRender(config);
            let handlers = [
                __inpup, __inpup, __backspace, __clear,
                __inpup, __inpup, __inpup, __inpup,
                __inpup, __inpup, __inpup, __inpup,
                __inpup, __inpup, __inpup, __inpup,
                __inpup, __inpup, __result, __inpup,
            ];
            this->but.forEach((but, i)=>{
                but.click(()=>handlers[i](this, but));
            });
        }
    }
}

function __inpup(self, but) {
    let val = self->input.value(),
        char = but.text(),
        last = val.slice(-1);
    if (last != '') {
        let t1 = (last.match(/[\d\.\(\)]/)) ? 1 : 0,
            t2 = (char.match(/[\d\.\(\)]/)) ? 1 : 0;
        if (t1 != t2) val += ' ';
    }
    val += char;
    self->input.value(val);
}

function __backspace(self, but) {
    let val = self->input.value();
    if (val.length == 0) return;
    val = val.slice(0, -1);
    val = val.trim();
    self->input.value(val);
}

function __clear(self, but) {
    self->input.value('');
}

function __result(self, but) {
    let val = self->input.value();
    if (val == '') return;
    self->input.value(lx.Math.parseToCalculate('=' + val));
}
