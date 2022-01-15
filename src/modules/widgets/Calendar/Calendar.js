#lx:module lx.Calendar;
#lx:module-data {
    i18n: i18n.yaml
};

#lx:use lx.MainCssContext;
#lx:use lx.CssColorSchema;
#lx:use lx.Date;
#lx:use lx.Box;
#lx:use lx.Input;

#lx:client {
    /*
     * Кэширует таблицу календаря - она нужна в единственном экземпляре,
     * не предусмотрено ситуаций, чтобы было раскрыто несколько календарей одновременно
     * соответственно таблица - синглтон, создается при первом вызове, потом достается из кэша
     */
    let __menu = null;
    let __oldDate = null;
    let __active = null;

    let _dayTitles = [ #lx:i18n(monday), #lx:i18n(tuesday), #lx:i18n(wednesday),
        #lx:i18n(thursday), #lx:i18n(friday), #lx:i18n(saturday), #lx:i18n(sunday)
    ];

    let _monthTitles = [ #lx:i18n(January), #lx:i18n(February), #lx:i18n(March), #lx:i18n(April),
        #lx:i18n(May), #lx:i18n(June), #lx:i18n(July), #lx:i18n(August),
        #lx:i18n(September), #lx:i18n(October), #lx:i18n(November), #lx:i18n(December)
    ];
}

class Calendar extends lx.Input #lx:namespace lx {
    getBasicCss() {
        return {
            main: 'lx-Calendar',

            arroyL: 'lx-Calendar-arroyL',
            arroyR: 'lx-Calendar-arroyR',
            month: 'lx-Calendar-month',
            dayTitle: 'lx-Calendar-dayTitle',
            today: 'lx-Calendar-today',

            daysTable: 'lx-Calendar-daysTable',
            day: 'lx-Calendar-day',
            sideDay: 'lx-Calendar-side-day',
            currentDay: 'lx-Calendar-current-day',

            monthTable: 'lx-Calendar-monthTable',
            monthItem: 'lx-Calendar-monthItem'
        };
    }

    static initCssAsset(css) {
        css.useContext(lx.MainCssContext.instance);
        css.inheritClass('lx-Calendar', 'Input');
        css.inheritClass('lx-Calendar-daysTable', 'AbstractBox');
        css.inheritClass('lx-Calendar-monthTable', 'AbstractBox');
        css.addClass('lx-Calendar-monthItem', {
            cursor: 'pointer'
        }, {
            hover: {
                backgroundColor: lx.CssColorSchema.checkedDarkColor
            }
        });
        css.addAbstractClass('lx-Calendar-arroy', {
            cursor: 'pointer',
            opacity: 0.5
        }, {
            hover: {
                opacity: 1
            }
        });
        css.inheritClasses({
            'lx-Calendar-arroyL' : { '@icon': ['\\2770', 16] },
            'lx-Calendar-arroyR': { '@icon': ['\\2771', 16] }
        }, 'lx-Calendar-arroy');
        css.inheritClass('lx-Calendar-month', 'Input', {
            cursor: 'pointer'
        });
        css.addClass('lx-Calendar-dayTitle', {
            background: lx.CssColorSchema.widgetGradient,
            color: lx.CssColorSchema.widgetIconColor
        });
        css.addClass('lx-Calendar-today', {
            background: lx.CssColorSchema.widgetGradient,
            color: lx.CssColorSchema.widgetIconColor,
            cursor: 'pointer'
        });
        css.addAbstractClass('lx-Calendar-every-day', {
            cursor: 'pointer'
        }, {
            hover: {
                backgroundColor: lx.CssColorSchema.checkedDarkColor
            }
        });
        css.inheritClasses({
            'lx-Calendar-day': {},
            'lx-Calendar-side-day': { color: 'gray' }
        }, 'lx-Calendar-every-day');
        css.addClass('lx-Calendar-current-day', {
            background: lx.CssColorSchema.widgetGradient,
            color: lx.CssColorSchema.widgetIconColor
        });
    }

    build(config) {
        this.date = config.date || (new Date()).toString();
        #lx:client {
            this.date = new lx.Date(this.date);
            this.value( this.date.format() );
        }
    }

    #lx:client {
        clientBuild(config) {
            super.clientBuild(config);
            this.on('mouseup', _handler_open);
            this.on('blur', _handler_blur);
        }

        postUnpack(config) {
            super.postUnpack(config);
            this.date = new lx.Date(this.date);
            this.value( this.date.format() );
        }
    }

    value(val) {
        if (val === undefined)
            return super.value();

        #lx:client { this.date.reset(val); }
        #lx:server { this.date = val; }
        super.value(val);
    }
}

#lx:client {
    function _handler_open() {
        __active = this;
        __oldDate = this.date.format();
        __renew();

        var menu = __getMenu();
        menu.show();
        menu.satelliteTo(this);
        lx.on('mouseup', _lxHandler_outclick);
    }

    function _handler_blur() {
        this.date.reset(this.value());
    }

    function _lxHandler_outclick(event) {
        if (__active === null) return;
        event = event || window.event;

        var widget = event.target.__lx;
        if (widget === __active) return;
        if (widget && widget.ancestor({is:__menu})) return;
        __close(event);
    }

    function __close(event) {
        let menu = __getMenu();
        menu.hide();
        menu->>monthContainer.hide();
        lx.off('mouseup', _lxHandler_outclick);

        if (__active.date.format() != __oldDate)
            __active.trigger('change', event);
        __active = null;
    }

    function __renew() {
        let date = __active.date,
            list = __getMenu(),
            actDate = date.getDate();

        __active.value(date.format());
        list->>year.value(date.getFullYear());
        list->>month.text(_monthTitles[date.getMonth()]);

        let firstDay = new lx.Date(date.getYear(), date.getMonth(), 1);
        while (firstDay.getDay() !== 1)
            firstDay.shiftDay(-1);
        let lastDay = new lx.Date(date.getYear(), date.getMonth(), date.getMaxDate());
        while (lastDay.getDay() !== 0)
            lastDay.shiftDay(1);
        let weeksCount = Math.round((lastDay.getDaysBetween(firstDay) + 1) / 7);

        let days = list->>days;
        days.del('day', 28, days.childrenCount('day') - 28);
        if (weeksCount > 4) {
            let newDays = days.add(lx.Box, 7 * (weeksCount - 4), { key: 'day' });
            newDays.forEach(day=>{
                day.align(lx.CENTER, lx.MIDDLE);
                day.click(_handler_setDate);
            });
        }
        days.get('day').forEach(day=>{
            day.removeClass(__active.basicCss.day);
            day.removeClass(__active.basicCss.sideDay);
            day.removeClass(__active.basicCss.currentDay);
            day.addClass((firstDay.getMonth() == date.getMonth()) ? __active.basicCss.day : __active.basicCss.sideDay);
            if (firstDay.format() == date.format()) day.addClass(__active.basicCss.currentDay);
            day.text(firstDay.getDate());
            day.__date = firstDay.format();
            firstDay.shiftDay(1);
        });

        list->>currentDay.text(#lx:i18n(today) + ': ' + (new lx.Date).format());
    }

    function _handler_setDate(event) {
        var calendar = __active;
        calendar.date.reset(this.__date);
        calendar.value( calendar.date.format() );
        __close(event);
    }

    function __getMenu() {
        if (__menu) return __menu;

        const calendarMenu = new lx.Box({
            parent: lx.body,
            key: 'calendarMenu',
            geom: true,
            size: ['320px', '320px'],
            depthCluster: lx.DepthClusterMap.CLUSTER_FRONT,
            style: { overflow: 'visible' }
        });

        let tableContainer = calendarMenu.add(lx.Box, { geom: true, css: __active.basicCss.daysTable });
        let monthContainer = calendarMenu.add(lx.Box,
            { geom: true, css: __active.basicCss.monthTable, key: 'monthContainer' }
        );

        tableContainer.stream();
        tableContainer.begin();
            let head = new lx.Box();
            head.grid({cols: 14, indent: '5px', minWidth: '10px'});
            head.add(lx.Box, { width: 1, css: __active.basicCss.arroyL, click:()=>{
                __active.date.shiftYear(-1);
                __renew();
            }});
            head.add(lx.Input, { width: 4, key: 'year' });
            head.add(lx.Box, { width: 1, css: __active.basicCss.arroyR, click:()=>{
                __active.date.shiftYear(1);
                __renew();
            }});
            head.add(lx.Box, { width: 1, css: __active.basicCss.arroyL, click:()=>{
                __active.date.shiftMonth(-1);
                __renew();
            }});
            head.add(lx.Box, { width: 6, key: 'month', css:__active.basicCss.month });
            head.add(lx.Box, { width: 1 , css: __active.basicCss.arroyR, click:()=>{
                __active.date.shiftMonth(1);
                __renew();
            }});

            let month = head->month;
            month.align(lx.CENTER, lx.MIDDLE);
            month.click(()=>monthContainer.show());

            let days = new lx.Box({key: 'days'});
            days.gridStream({cols:7});
            days.add(lx.Box, 7, { key: 'dayTitle', css: __active.basicCss.dayTitle }, {
                preBuild: function (config, i) {
                    config.text = _dayTitles[i];
                    return config;
                }
            });
            days.get('dayTitle').forEach(box=>box.align(lx.CENTER, lx.MIDDLE));
            let newDays = days.add(lx.Box, 28, { key: 'day' });
            newDays.forEach(day=>{
                day.align(lx.CENTER, lx.MIDDLE);
                day.click(_handler_setDate);
            });
            (new lx.Box({ key: 'currentDay', css: __active.basicCss.today, click:(e)=>{
                __active.date.reset();
                __active.value(__active.date.format());
                __close(e);
            }})).align(lx.CENTER, lx.MIDDLE);
        tableContainer.end();

        monthContainer.gridProportional({cols: 2});
        monthContainer.add(lx.Box, 12, {css:__active.basicCss.monthItem}, {
            postBuild: function (el, i) {
                el.text(_monthTitles[i]);
                el.align(lx.CENTER, lx.MIDDLE);
                el.click(()=>{
                    monthContainer.hide();
                    __active.date.setMonth(i);
                    __renew();
                });
            }
        });
        monthContainer.hide();

        __menu = calendarMenu;
        return calendarMenu;
    }
}
