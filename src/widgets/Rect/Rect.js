#lx:module lx.Rect;

/**
 * @group {i18n:widgets}
 * */
class Rect #lx:namespace lx {
    constructor(config = {}) {
        this.__construct();
        
        //!!!! взято с клиента, серверу может быть не надо
        if (config === false) return;

        config = this.modifyConfigBeforeApply(config);
        this.defineDomElement(config);
        this.applyConfig(config);
        this.build(config);

        #lx:client { this.postBuild(config); };

        this.type = this.lxClassName;
        var namespace = this.lxNamespace;
        if (namespace != 'lx') this._namespace = namespace;
    }

    #lx:server {
        __construct() {
            this.__self = {
                domElem: null,
                parent: null
            };
        }

        get domElem() { return this.__self.domElem; }
        set domElem(attr) { this.__self.domElem = attr; }

        get parent() { return this.__self.parent; }
        set parent(attr) { this.__self.parent = attr; }

        beforePack() { }
    }

    #lx:client __construct() {
        this.domElem = null;
        this.parent = null;
    }

    static getStaticTag() {
        return 'div';
    }

    modifyConfigBeforeApply(config) {
        return config;
    }

    /**
     * Строительство элемента на и на клиенте и на сервере
     * */
    build(config={}) {
    }

    /**
     * Непосредственно создание DOM-элемента, выстраивание связи с родителем
     * */
    defineDomElement(config) {
        if (this.getDomElem()) {
            this.log('already exists');
            return;
        }

        var tag = config.tag || self::getStaticTag();
        this.domElem = new lx.DomElementDefinition(this, tag);

        if (config.key) this.key = config.key;
        else if (config.field) this.key = config.field;

        this.setParent(config);
    }

    /**
     *	field
     *	html
     *	basicCss
     *	cssClass
     *	style
     *	click
     *	move | parentResize | parentMove
     * */
    applyConfig(config={}) {
        if (config.field) this._field = config.field;
        if (config.html) this.html(config.html);

        this.applyBasicCss(config);
        if (config.css !== undefined) this.addClass(config.css);

        if (config.style) {
            for (var i in config.style) {
                if (i in this) {
                    if ( config.style[i].isArray )
                        this[i].apply( this, config.style[i] );
                    else
                        this[i].call( this, config.style[i] );
                } else {
                    if ( config.style[i].isArray )
                        this.style.apply( this, i, config.style[i] );
                    else
                        this.style.call( this, i, config.style[i] );
                }
            }
        }

        if (config.picture) this.picture(config.picture);

        if (config.click) this.click( config.click );
        if (config.blur) this.blur( config.blur );

        if (config.move) this.move(config.move);
        else if (config.parentResize) this.move({parentResize: true});
        else if (config.parentMove) this.move({parentMove: true});

        return this;
    }

    /**
     * Статический метод для массового создания сущностей
     * */
    static construct(count, config, configurator={}) {
        var c = new lx.Collection();

        // Оптимизация массовой вставки в родителя
        var parent = null;
        if (config.before) {
            parent = config.before.parent;
        } else if (config.after) {
            parent = config.after.parent;
        } else if (config.parent) parent = config.parent;
        if (parent === null && config.parent !== null)
            parent = lx.WidgetHelper.autoParent;

        config.parent = parent;
        if (parent) parent.useRenderCache();
        for (var i=0; i<count; i++) {
            var modifConfig = config;
            if (configurator.preBuild) modifConfig = configurator.preBuild.call(null, modifConfig, i);
            var obj = new this(modifConfig);
            c.add(obj);
            if (configurator.postBuild) configurator.postBuild.call(null, obj, i);
        };
        if (parent) parent.applyRenderCache();

        return c;
    }

    /**
     * Метод для освобождения ресурсов
     * */
    destruct() {
        this.trigger('beforeDestruct');
        this.destructProcess();
        this.parent = null;
        if (this.domElem) this.domElem.clear();
        this.domElem = null;
        this.trigger('afterDestruct');
    }

    destructProcess() {}

    #lx:client {
        /**
         * Метод, вызываемый в конструкторе для выполенния действий, когда сущность действительно построена, связи (с родителем) выстроены
         * Логика общая для создания элемента и на клиенте и для допиливания при получении от сервера
         * */
        postBuild(config={}) {
            this.on('scroll', lx.checkDisplay);
            if (!config) return;
            if (config.disabled !== undefined) this.disabled(config.disabled);
        }

        /**
         * Восстановление при загрузке
         * */
        static rise(elem) {
            var el = new this(false);

            el.domElem = new lx.DomElementDefinition(el);
            el.domElem.setElem(elem);

            var data = elem.getAttribute('lx-data');
            if (data) {
                var arr = data.split(/\s*,\s*/);
                arr.each((pare)=> {
                    pare = pare.split(/\s*:\s*/);
                    if (!(pare[0] in el)) el[pare[0]] = pare[1];
                });
            }
            return el;
        }
    }
    /* 1. Constructor */
    //==================================================================================================================

    
    //==================================================================================================================
    /* 2. Common */
    get index() {
        if (this._index === undefined) return 0;
        return this._index;
    }

    /**
     * Ключ с учетом индексации (если она есть) - уникальное значение в пределах родителя
     * */
    indexedKey() {
        if (this._index === undefined) return this.key;
        return this.key + '[' + this._index + ']';
    }

    /**
     * Путь для запроса изображений (идея единого хранилища изображений в рамках модуля)
     * */
    imagePath(name) {
        if (name[0] == '/') return name;

        var plugin = this.getPlugin();
        return plugin.getImage(name);
    }

    /**
     * Управление активностью элемента
     * */
    disabled(bool) {
        if (bool === undefined) return this.domElem.getAttribute('disabled') !== null;

        if (bool) this.domElem.setAttribute('disabled', '');
        else this.domElem.removeAttribute('disabled');
        return this;
    }

    /* 2. Common */
    //==================================================================================================================


    //==================================================================================================================
    /* 3. Html and Css */
    getDomElem() {
        if (!this.domElem) return null;
        return this.domElem.getElem();
    }

    tag() {
        return this.domElem.getTagName();
    }

    setAttribute(name, val = '') {
        if (val === null) {
            this.domElem.removeAttribute(name);
            return this;
        }

        this.domElem.setAttribute(name, val);
        return this;
    }

    getAttribute(name) {
        return this.domElem.getAttribute(name);
    }

    removeAttribute(name) {
        return this.domElem.removeAttribute(name);
    }

    style(name, val) {
        if (name === undefined) return this.domElem.style();

        if (name.isObject) {
            for (var i in name) this.style(i, name[i]);
            return this;
        }

        if (val === undefined) return this.domElem.style(name);

        this.domElem.style(name, val);
        return this;
    }

    html(content) {
        if (content == undefined)
            return this.domElem.html();

        this.domElem.html(content);
        return this;
    }

    applyBasicCss(config) {
        var basicCss = config.basicCss;
        if (!basicCss) {
            var plugin = this.getPlugin();
            if (plugin) {
                basicCss = plugin.getWidgetBasicCss(this.lxFullClassName);
            }
        }
        if (!basicCss) basicCss = this.getBasicCss();
        if (basicCss) this.setBasicCss(basicCss);
    }

    /**
     * Можно переопределять у виджетов для установки стилей по умолчанию
     */
    getBasicCss() {
        return false;
    }

    /**
     * Можно переопределять у виджетов - алгоритм расстановки классов, возвращенных
     * методом [[getBasicCss()]]
     */
    setBasicCss(classes) {
        if (classes.isString) this.addClass(classes);
        else if (classes.isObject) {
            if (classes.main) this.addClass(classes.main);
            this.basicCss = classes;
        }
        return this;
    }

    /**
     * Можно передавать аргументы двумя путями:
     * 1. elem.addClass(class1, class2);
     * 2. elem.addClass([class1, class2]);
     * */
    addClass(...args) {
        if (args[0].isArray) args = args[0];

        args.each((name)=> {
            if (name == '') return;
            this.domElem.addClass(name);
        });
        return this;
    }

    /**
     * Можно передавать аргументы двумя путями:
     * 1. elem.removeClass(class1, class2);
     * 2. elem.removeClass([class1, class2]);
     * */
    removeClass(...args) {
        if (args[0].isArray) args = args[0];

        args.each((name)=> {
            if (name == '') return;
            this.domElem.removeClass(name);
        });
        return this;
    }

    /**
     * Проверить имеет ли элемент css-класс
     * */
    hasClass(name) {
        return this.domElem.hasClass(name);
    }

    /**
     * Если элемент имеет один из классов, то он будет заменен на второй
     * Если передан только один класс - он будет установлен, если его не было, либо убран, если он был у элемента
     * */
    toggleClass(class1, class2='') {
        if (this.hasClass(class1)) {
            this.removeClass(class1);
            this.addClass(class2);
        } else {
            this.addClass(class1);
            this.removeClass(class2);
        }
    }

    /**
     * Если condition==true - первый класс применяется, второй убирается
     * Если condition==false - второй класс применяется, первый убирается
     * */
    toggleClassOnCondition(condition, class1, class2='') {
        if (condition) {
            this.addClass(class1);
            this.removeClass(class2);
        } else {
            this.removeClass(class1);
            this.addClass(class2);
        }
    }

    /**
     * Убрать все классы
     * */
    clearClasses() {
        this.domElem.clearClasses();
        return this;
    }

    opacity(val) {
        if (val != undefined) {
            this.style('opacity', val);
            return this;
        }
        return this.style('opacity');
    }

    fill(color) {
        this.style('backgroundColor', color);
        return this;
    }

    overflow(val) {
        this.style('overflow', val);
        // if (val == 'auto') this.on('scroll', lx.checkDisplay);
        return this;
    }

    picture(pic) {
        if (pic === undefined)
            return this.domElem.style.backgroundImage.split('"')[1];

        if (pic === '' || !pic) this.domElem.style('backgroundImage', 'url()');
        else {
            var path = this.imagePath(pic);
            this.domElem.style('backgroundImage', 'url(' + path + ')');
            this.domElem.style('backgroundRepeat', 'no-repeat');
            this.domElem.style('backgroundSize', '100% 100%');
        }
        return this;
    }

    border( info ) {  // info = {width, color, style, side}
        if (info == undefined) info = {};
        var width = ( (info.width != undefined) ? info.width: 1 ) + 'px',
            color = (info.color != undefined) ? info.color: '#000000',
            style = (info.style != undefined) ? info.style: 'solid',
            sides = (info.side != undefined) ? info.side: 'ltrb',
            side = [false, false, false, false],
            sideName = ['Left', 'Top', 'Right', 'Bottom'];
        side[0] = (sides.search('l') != -1);
        side[1] = (sides.search('t') != -1);
        side[2] = (sides.search('r') != -1);
        side[3] = (sides.search('b') != -1);

        if (side[0] && side[1] && side[2] && side[3]) {
            this.domElem.style('borderWidth', width);
            this.domElem.style('borderColor', color);
            this.domElem.style('borderStyle', style);
        } else {
            for (var i=0; i<4; i++) if (side[i]) {
                this.domElem.style('border' + sideName[i] + 'Width', width);
                this.domElem.style('border' + sideName[i] + 'Color', color);
                this.domElem.style('border' + sideName[i] + 'Style', style);
            }
        }
        this.trigger('resize');
        return this;
    }

    /**
     * Варианты аргумента val:
     * 1. Число - скругление по всем углам в пикселях
     * 2. Объект: {
     *     side: string    // указание углов для скругления в виде 'tlbr'
     *     value: integer  // скругление по всем углам в пикселях
     * }
     * */
    roundCorners(val) {
        var arr = [];
        if (val.isObject) {
            var t = false, b = false, l = false, r = false;
            if ( val.side.indexOf('tl') != -1 ) { t = true; l = true; arr.push('TopLeft'); }
            if ( val.side.indexOf('tr') != -1 ) { t = true; r = true; arr.push('TopRight'); }
            if ( val.side.indexOf('bl') != -1 ) { b = true; l = true; arr.push('BottomLeft'); }
            if ( val.side.indexOf('br') != -1 ) { b = true; r = true; arr.push('BottomRight'); }
            if ( !t && val.side.indexOf('t') != -1 ) { arr.push('TopLeft'); arr.push('TopRight'); }
            if ( !b && val.side.indexOf('b') != -1 ) { arr.push('BottomLeft'); arr.push('BottomRight'); }
            if ( !l && val.side.indexOf('l') != -1 ) { arr.push('TopLeft'); arr.push('BottomLeft'); }
            if ( !r && val.side.indexOf('r') != -1 ) { arr.push('TopRight'); arr.push('BottomRight'); }
            val = val.value;
        }
        if (val.isNumber) val += 'px';

        if (!arr.length) this.domElem.style('borderRadius', val);

        for (var i=0; i<arr.length; i++)
            this.domElem.style('border' + arr[i] + 'Radius', val);

        return this;
    }

    rotate(angle) {
        this.domElem.style('mozTransform', 'rotate(' + angle + 'deg)');    // Для Firefox
        this.domElem.style('msTransform', 'rotate(' + angle + 'deg)');     // Для IE
        this.domElem.style('webkitTransform', 'rotate(' + angle + 'deg)'); // Для Safari, Chrome, iOS
        this.domElem.style('oTransform', 'rotate(' + angle + 'deg)');      // Для Opera
        this.domElem.style('transform', 'rotate(' + angle + 'deg)');
        return this;
    }

    scrollTo(adr) {
        if (adr.isObject) {
            if (adr.x !== undefined) this.domElem.param('scrollLeft', +adr.x);
            if (adr.y !== undefined) this.domElem.param('scrollTop', +adr.y);
        } else this.domElem.param('scrollTop', adr);
        this.trigger('scroll');
        return this;
    }

    scrollPos() {
        return {
            x: this.domElem.param('scrollLeft'),
            y: this.domElem.param('scrollTop')
        };
    }

    visibility(vis) {
        if (vis !== undefined) { vis ? this.show(): this.hide(); return this; }

        if ( !this.domElem.style('visibility') || this.domElem.style('visibility') == 'inherit' ) {
            var p = this.domElem.parent;
            while (p) { if (p.domElem.style('visibility') == 'hidden') return false; p = p.domElem.parent; }
            return true;
        } else return (this.domElem.style('visibility') != 'hidden')
    }

    show() {
        this.domElem.style('visibility', 'inherit');
        #lx:client{ lx.checkDisplay.call(this); }
        return this;
    }

    hide() {
        this.domElem.style('visibility', 'hidden');
        #lx:client{ lx.checkDisplay.call(this); }
        return this;
    }

    toggleVisibility() {
        if (this.visibility()) this.hide();
        else this.show();
    }

    #lx:client setDomElement(elem) {
        this.del();
        this.domElem.setElem(elem);
        return this;
    }
    /* 3. Html and Css */
    //==================================================================================================================


    //==================================================================================================================
    /* 4. Geometry */
    /**
     * Размер без рамок, полос прокрутки и т.п.
     * */
    getInnerSize(param) {
        if (param === undefined) return [
            this.domElem.param('clientWidth'),
            this.domElem.param('clientHeight')
        ];
        if (param == lx.HEIGHT) return this.domElem.param('clientHeight');
        if (param == lx.WIDTH) return this.domElem.param('clientWidth');
    }

    /**
     * Установка значения геометрическому параметру с учетом проверки родительской стратегией позиционирования
     * */
    setGeomParam(param, val) {
        /*
        todo
        тут не актуализируется стратегия позиционирования для потомков - отсюда она и не может, потому что Rect не имеет потомков
        надо сделать этот метод шалонным - оператор this.parent.tryChildReposition(this, param, val); вынести в отдельный метод,
        и переопределить его в Box, чтобы там актуализировать стратегию
        + добавить метод Box.positioningAutoActualizeState(boolean) - чтобы включать/выключать/проверять режим авто-актуализации
        + добавить в метод Rect.coords поддержку передачи параметров конфигом, передачу 4-х методов (l, t, r, b)
        + методы Rect.coords и Rect.size переопределить в Box с выключением автоактуализации
        + добавить метод Rect.geom(l, t, w, h, r, b) с возможностью передачи параметров конфигом + переопределить его в Box с выключением автоактуализации
        Для всего этого надо перепроверить как работают все стратегии во всех вариантах, продумать их взаимодействия
        и, возможно, прижется сделать метод аналогичный этому, но без актуализации - для использования родительской стратегией
        */
        if (this.parent) this.parent.tryChildReposition(this, param, val);
        else {
            this.geomPriority(param);
            this.domElem.style(lx.Geom.geomName(param), val);
        }
        return this;
    }

    /**
     * Если в силу внутренних процессов изменился размер - о таком надо сообщить "наверх" по иерархии
     * */
    reportSizeChange(param) {
        if (this.parent) this.parent.childHasAutoresized(this);
    }

    /**
     * format = '%' | 'px'  - вернет значение float, в соответствии с переданным форматом
     * если format не задан - вернет значение как оно записано в style
     * */
    getLeft(format) {
        if (!format) return this.domElem.style('left');

        var elem = this.getDomElem();
        if (!elem) return null;

        var pw = (this.domElem.parent) ? this.domElem.parent.getDomElem().offsetWidth : elem.offsetWidth;
        return this.calcGeomParam(format, elem.style.left, elem.offsetLeft, pw);
    }

    left(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getLeft(val);
        return this.setGeomParam(lx.LEFT, val);
    }

    getRight(format) {
        if (!format) return this.domElem.style('right');

        var elem = this.getDomElem();
        if (!elem) return null;

        if (!this.domElem.parent) return undefined;
        pElem = this.domElem.parent.getDomElem();
        if (!pElem) return undefined;
        if (elem.style.right != '') {
            var b = lx.Geom.splitGeomValue(elem.style.right);
            if (format == '%') {
                if ( b[1] != '%' ) b[0] = (b[0] / pElem.offsetWidth) * 100;
                return b[0];
            } else {
                if ( b[1] != 'px' ) b[0] = b[0] * pElem.offsetWidth * 0.01;
                return b[0];
            }
        } else {
            var t = lx.Geom.splitGeomValue(elem.style.left),
                h = lx.Geom.splitGeomValue(elem.style.width),
                pw = pElem.offsetWidth;
            if (format == '%') {
                if ( t[1] != '%' ) t[0] = (t[0] / pw) * 100;
                if ( h[1] != '%' ) h[0] = (h[0] / pw) * 100;
                return 100 - t[0] - h[0];
            } else {
                if ( t[1] != 'px' ) t[0] = elem.offsetLeft;
                if ( h[1] != 'px' ) h[0] = elem.offsetWidth;
                return pw - t[0] - h[0];
            }
        }
    }

    right(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getRight(val);
        return this.setGeomParam(lx.RIGHT, val);
    }

    getTop(format) {
        if (!format) return this.domElem.style('top');

        var elem = this.getDomElem();
        if (!elem) return null;

        var p = this.domElem.parent,
            ph = (p) ? p.getDomElem().offsetHeight : elem.offsetHeight;
        return this.calcGeomParam(format, elem.style.top, elem.offsetTop, ph);
    }

    top(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getTop(val);
        return this.setGeomParam(lx.TOP, val);
    }

    getBottom(format) {
        if (!format) return this.domElem.style('bottom');

        var elem = this.getDomElem();
        if (!elem) return null;

        if (!this.domElem.parent) return undefined;
        pElem = this.domElem.parent.getDomElem();
        if (!pElem) return undefined;
        if (elem.style.bottom != '') {
            var b = lx.Geom.splitGeomValue(elem.style.bottom);
            if (format == '%') {
                if ( b[1] != '%' ) b[0] = (b[0] / pElem.clientHeight) * 100;
                return b[0];
            } else {
                if ( b[1] != 'px' ) b[0] = b[0] * pElem.clientHeight * 0.01;
                return b[0];
            }
        } else {
            var t = lx.Geom.splitGeomValue(elem.style.top),
                h = lx.Geom.splitGeomValue(elem.style.height),
                ph = pElem.clientHeight;
            if (format == '%') {
                if ( t[1] != '%' ) t[0] = (t[0] / ph) * 100;
                if ( h[1] != '%' ) h[0] = (h[0] / ph) * 100;
                return 100 - t[0] - h[0];
            } else {
                if ( t[1] != 'px' ) t[0] = elem.offsetTop;
                if ( h[1] != 'px' ) h[0] = elem.offsetHeight;
                return ph - t[0] - h[0];
            }
        }
    }

    bottom(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getBottom(val);
        return this.setGeomParam(lx.BOTTOM, val);
    }

    getWidth(format) {
        if (!format) return this.domElem.style('width');

        var elem = this.getDomElem();
        if (!elem) return null;

        if (!this.domElem.parent) {
            if (format == '%') return 100;
            return elem.offsetWidth;
        }
        return this.calcGeomParam(format, elem.style.width,
            elem.offsetWidth, this.domElem.parent.getDomElem().offsetWidth);
    }

    width(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getWidth(val);
        return this.setGeomParam(lx.WIDTH, val);
    }

    getHeight(format) {
        if (!format) return this.domElem.style('height');

        var elem = this.getDomElem();
        if (!elem) return null;

        if (!this.domElem.parent) {
            if (format == '%') return 100;
            return elem.offsetHeight;
        }
        return this.calcGeomParam(format, elem.style.height,
            elem.offsetHeight, this.domElem.parent.getDomElem().offsetHeight);
    }

    height(val) {
        if (val === undefined || val == '%' || val == 'px')
            return this.getHeight(val);
        return this.setGeomParam(lx.HEIGHT, val);
    }

    coords(l, t) {
        if (l === undefined) return [ this.left(), this.top() ];
        if (t === undefined) return [ this.left(l), this.top(l) ];
        this.left(l);
        this.top(t);
        return this;
    }

    size(w, h) {
        if (w === undefined) return [ this.width(), this.height() ];
        if (h === undefined) return [ this.width(w), this.height(w) ];
        this.width(w);
        this.height(h);
        return this;
    }

    setGeom(geom) {
        if (geom[0] !== null && geom[0] !== undefined) this.left(geom[0]);
        if (geom[1] !== null && geom[1] !== undefined) this.top(geom[1]);
        if (geom[2] !== null && geom[2] !== undefined) this.width(geom[2]);
        if (geom[3] !== null && geom[3] !== undefined) this.height(geom[3]);
        if (geom[4] !== null && geom[4] !== undefined) this.right(geom[4]);
        if (geom[5] !== null && geom[5] !== undefined) this.bottom(geom[5]);

        this.trigger('resize');
    }

    getGeomMask(units = undefined) {
        var result = {};
        result.pH = this.geomPriorityH().lxClone();
        result.pV = this.geomPriorityV().lxClone();
        result[result.pH[0]] = this[lx.Geom.geomName(result.pH[0])](units);
        result[result.pH[1]] = this[lx.Geom.geomName(result.pH[1])](units);
        result[result.pV[0]] = this[lx.Geom.geomName(result.pV[0])](units);
        result[result.pV[1]] = this[lx.Geom.geomName(result.pV[1])](units);
        if (this.geom) result.geom = this.geom.lxClone();
        return result;
    }

    /**
     * Копия "как есть" - с приоритетами, без адаптаций под старые соответствующие значения
     * */
    copyGeom(geomMask, units = undefined) {
        if (geomMask instanceof lx.Rect) geomMask = geomMask.getGeomMask(units);

        var pH = geomMask.pH,
            pV = geomMask.pV;
        this.setGeomParam(pH[1], units ? geomMask[pH[1]] + units : geomMask[pH[1]]);
        this.setGeomParam(pH[0], units ? geomMask[pH[0]] + units : geomMask[pH[0]]);
        this.setGeomParam(pV[1], units ? geomMask[pV[1]] + units : geomMask[pV[1]]);
        this.setGeomParam(pV[0], units ? geomMask[pV[0]] + units : geomMask[pV[0]]);
        if (geomMask.geom) this.geom = geomMask.geom.lxClone();

        this.trigger('resize');

        return this;
    }

    getGlobalGeomMask(units = undefined) {
        var result = {};
        result.pH = this.geomPriorityH().lxClone();
        result.pV = this.geomPriorityV().lxClone();
        //TODO - возвращает только в пикселях, units - не работает
        var rect = this.globalRect();
        result[result.pH[0]] = rect[lx.Geom.geomName(result.pH[0])];
        result[result.pH[1]] = rect[lx.Geom.geomName(result.pH[1])];
        result[result.pV[0]] = rect[lx.Geom.geomName(result.pV[0])];
        result[result.pV[1]] = rect[lx.Geom.geomName(result.pV[1])];
        if (this.geom) result.geom = this.geom.lxClone();
        return result;
    }

    /**
     * Копия "как есть" - с приоритетами, без адаптаций под старые соответствующие значения
     * */
    copyGlobalGeom(geomMask, units = undefined) {
        if (geomMask instanceof lx.Rect) geomMask = geomMask.getGlobalGeomMask(units);

        var pH = geomMask.pH,
            pV = geomMask.pV;
        this.setGeomParam(pH[1], units ? geomMask[pH[1]] + units : geomMask[pH[1]]);
        this.setGeomParam(pH[0], units ? geomMask[pH[0]] + units : geomMask[pH[0]]);
        this.setGeomParam(pV[1], units ? geomMask[pV[1]] + units : geomMask[pV[1]]);
        this.setGeomParam(pV[0], units ? geomMask[pV[0]] + units : geomMask[pV[0]]);
        if (geomMask.geom) this.geom = geomMask.geom.lxClone();

        this.trigger('resize');

        return this;
    }

    geomPriority(param) {
        if (lx.Geom.directionByGeom(param) == lx.HORIZONTAL)
            this.geomPriorityH(param);
        else this.geomPriorityV(param);
    }

    geomPriorityH(val, val2) {
        if (val === undefined) return ((this.geom) ? this.geom.bpg : 0) || [lx.LEFT, lx.CENTER];

        if (val2 !== undefined) {
            if (!this.geom) this.geom = {};
            var dropGeom = this.geomPriorityH().diff([val, val2])[0];
            if (dropGeom === undefined) return this;
            this.domElem.style(lx.Geom.geomName(dropGeom), '');
            this.geom.bpg = [val, val2];
            return this;
        }

        if (!this.geom) this.geom = {};

        if (!this.geom.bpg) this.geom.bpg = [lx.LEFT, lx.CENTER];

        if (this.geom.bpg[0] == val) return this;

        if (this.geom.bpg[1] != val) switch (this.geom.bpg[1]) {
            case lx.LEFT: this.domElem.style('left', ''); break;
            case lx.CENTER: this.domElem.style('width', ''); break;
            case lx.RIGHT: this.domElem.style('right', ''); break;
        }

        this.geom.bpg[1] = this.geom.bpg[0];
        this.geom.bpg[0] = val;

        return this;
    }

    geomPriorityV(val, val2) {
        if (val === undefined) return ((this.geom) ? this.geom.bpv : 0) || [lx.TOP, lx.MIDDLE];

        if (val2 !== undefined) {
            if (!this.geom) this.geom = {};
            var dropGeom = this.geomPriorityV().diff([val, val2])[0];
            if (dropGeom === undefined) return this;
            this.domElem.style(lx.Geom.geomName(dropGeom), '');
            this.geom.bpv = [val, val2];
            return this;
        }

        if (!this.geom) this.geom = {};
        if (!this.geom.bpv) this.geom.bpv = [lx.TOP, lx.MIDDLE];

        if (this.geom.bpv[0] == val) return this;

        if (this.geom.bpv[1] != val) switch (this.geom.bpv[1]) {
            case lx.TOP: this.domElem.style('top', ''); break;
            case lx.MIDDLE: this.domElem.style('height', ''); break;
            case lx.BOTTOM: this.domElem.style('bottom', ''); break;
        }

        this.geom.bpv[1] = this.geom.bpv[0];
        this.geom.bpv[0] = val;

        return this;
    }

    /**
     * Вычисляет процентное или пиксельное представление размера, переданного в любом формате, с указанием направления - длина или высота
     * Пример:
     * elem.geomPart('50%', 'px', VERTICAL)  - вернет половину высоты элемента в пикселах
     * */
    geomPart(val, unit, direction) {
        if (val.isNumber) return +val;
        if (!val.isString) return NaN;

        var num = parseFloat(val),
            baseUnit = val.split(num)[1];

        if (baseUnit == unit) return num;

        if (unit == '%') {
            return (direction == lx.HORIZONTAL)
                ? (num * 100) / this.width('px')
                : (num * 100) / this.height('px');
        }

        if (unit == 'px') {
            return (direction == lx.HORIZONTAL)
                ? num * this.width('px') * 0.01
                : num * this.height('px') * 0.01;
        }

        return NaN;
    }

    /**
     * Расчет для возврата значения в нужном формате
     * val - как значение записано в стиле
     * thisSize - размер самого элемента в пикселях
     * parentSize - размер родительского элемента в пикселях
     * */
    calcGeomParam(format, val, thisSize, parentSize) {
        if (format == 'px') return thisSize;

        if (val == null) return null;

        if ( val.charAt( val.length - 1 ) == '%' ) {
            if (format == '%') return parseFloat(val);
            return thisSize;
        }

        return ( thisSize * 100 ) / parentSize;
    }

    rect(format='px') {
        var l = this.left(format),
            t = this.top(format),
            w = this.width(format),
            h = this.height(format);
        return {
            left: l,
            top: t,
            width: w,
            height: h,
            right: l + w,
            bottom: t + h
        }
    }

    globalRect() {
        var elem = this.getDomElem();
        if (!elem) return {};
        var rect = elem.getBoundingClientRect();
        return {
            top: rect.top,
            left: rect.left,
            width: rect.width,
            height: rect.height,
            bottom: window.screen.availHeight - rect.bottom,
            right: window.screen.availWidth - rect.right
        }
    }

    containPoint(x, y) {
        var rect = this.globalRect();
        return (
            x >= rect.left
            && x <= (rect.left + rect.width)
            && y >= rect.top
            && y <= (rect.top + rect.height)
        );
    }
    
    globalPointToInner(point) {
        var y = point.lxGetFirstDefined(['y', 'clientY'], null),
            x = point.lxGetFirstDefined(['x', 'clientX'], null);
        if (x === null || y === null) return false;

        var rect = this.globalRect();
        return {
            x: x - rect.left,
            y: y - rect.top
        };
    }

    /**
     * Провека не выходит ли элемент за пределы видимости вверх по иерархии родителей
     * Родитель, с которого надо начать проверять может быть передан явно (н-р если непосредственный родитель заведомо содержит данный элемент вне своей геометрии)
     * */
    isOutOfVisibility(el = null) {
        if (el === null) el = this.domElem.parent;

        var result = {},
            rect = this.globalRect(),
            l = rect.left,
            r = rect.left + rect.width,
            t = rect.top,
            b = rect.top + rect.height,
            p = el;

        while (p) {
            var pRect = p.globalRect(),
                elem = p.getDomElem(),
                pL = pRect.left,
                pR = pRect.left + pRect.width + elem.clientWidth - elem.offsetWidth,
                pT = pRect.top,
                pB = pRect.top + pRect.height + elem.clientHeight - elem.offsetHeight;

            if (l < pL) result.left   = pL - l;
            if (r > pR) result.right  = pR - r;
            if (t < pT) result.top    = pT - t;
            if (b > pB) result.bottom = pB - b;

            if (!result.lxEmpty) {
                result.element = p;
                return result;
            }

            p = p.domElem.parent;
        }

        return result;
    }

    parentScreenParams() {
        if (!this.domElem.parent) {
            var left = window.pageXOffset || document.documentElement.scrollLeft,
                width = window.screen.availWidth,
                right = left + width,
                top = window.pageYOffset || document.documentElement.scrollTop,
                height = window.screen.availHeight,
                bottom = top + height;
            return { left, right, width, height, top, bottom };
        }

        var elem = this.domElem.parent.getDomElem(),
            left = elem.scrollLeft,
            width = elem.offsetWidth,
            right = left + width,
            top = elem.scrollTop,
            height = elem.offsetHeight,
            bottom = top + height;

        return { left, right, width, height, top, bottom };
    }

    /**
     * Проверка не выходит ли элемент за пределы своего родителя
     * */
    isOutOfParentScreen() {
        var p = this.domElem.parent,
            rect = this.rect('px'),
            geom = this.parentScreenParams(),
            result = {};

        if (rect.left < geom.left) result.left = geom.left - rect.left;
        if (rect.right > geom.right)  result.right = geom.right - rect.right;
        if (rect.top < geom.top)  result.top = geom.top - rect.top;
        if (rect.bottom > geom.bottom) result.bottom = geom.bottom - rect.bottom;

        if (result.lxEmpty) return false;
        return result;
    }

    returnToParentScreen() {
        var out = this.isOutOfParentScreen();
        if (out.lxEmpty) return this;

        if (out.left && out.right) {
        } else {
            if (out.left) this.left( this.left('px') + out.left + 'px' );
            if (out.right) this.left( this.left('px') + out.right + 'px' );
        }
        if (out.top && out.bottom) {
        } else {
            if (out.top) this.top( this.top('px') + out.top + 'px' );
            if (out.bottom) this.top( this.top('px') + out.bottom + 'px' );
        }
        return this;
    }

    /**
     * Отображается ли элемент в данный момент
     * */
    isDisplay() {
        if (!this.visibility()) return false;

        var r = this.globalRect(),
            w = window.screen.availWidth,
            h = window.screen.availHeight;

        if (r.top > h) return false;
        if (r.bottom < 0) return false;
        if (r.left > w) return false;
        if (r.right < 0) return false;
        return true;
    }

    /**
     * Примеры использования:
     * 1. small.locateBy(big, lx.RIGHT);
     * 2. small.locateBy(big, lx.RIGHT, 5);
     * 3. small.locateBy(big, [lx.RIGHT, lx.BOTTOM]);
     * 4. small.locateBy(big, {bottom: '20%', right: 20});
     * */
    locateBy(elem, align, step) {
        if (align.isArray) {
            for (var i=0,l=align.len; i<l; i++) this.locateBy(elem, align[i]);
            return this;
        } else if (align.isObject) {
            for (var i in align) this.locateBy(elem, lx.Geom.alignConst(i), align[i]);
            return this;
        }

        if (!step) step = 0;
        step = elem.geomPart(step, 'px', lx.Geom.directionByGeom(align));
        var rect = elem.globalRect();
        switch (align) {
            case lx.TOP:
                this.geomPriorityV(lx.TOP, lx.MIDDLE);
                this.top( rect.top+step+'px' );
                break;
            case lx.BOTTOM:
                this.geomPriorityV(lx.BOTTOM, lx.MIDDLE);
                this.bottom( rect.bottom+step+'px' );
                break;
            case lx.MIDDLE:
                this.geomPriorityV(lx.TOP, lx.MIDDLE);
                this.top( rect.top + (elem.height('px') - this.height('px')) * 0.5 + step + 'px' );
                break;

            case lx.LEFT:
                this.geomPriorityH(lx.LEFT, lx.CENTER);
                this.left( rect.left+step+'px' );
                break;
            case lx.RIGHT:
                this.geomPriorityH(lx.RIGHT, lx.CENTER);
                this.right( rect.right+step+'px' );
                break;
            case lx.CENTER:
                this.geomPriorityH(lx.LEFT, lx.CENTER);
                this.left( rect.left + (elem.width('px') - this.width('px')) * 0.5 + step + 'px' );
                break;
        };
        return this;
    }

    satelliteTo(elem) {
        this.locateBy(elem, {top: elem.height('px'), center:0});
        if (this.isOutOfParentScreen().bottom)
            this.locateBy(elem, {bottom: elem.height('px'), center:0});
        this.returnToParentScreen();
    }
    /* 4. Geometry */
    //==================================================================================================================


    //==================================================================================================================
    /* 5. Environment managment */
    /**
     * config == Rect | {
     *     parent: Box    // непосредственно родитель, если null - родитель не выставляется
     *     index: int     // если в родителе по ключу элемента есть группа, можно задать конкретную позицию
     *     before: Rect   // если в родителе по ключу элемента есть группа, можно его расположить перед указанным элементом из группы
     *     after: Rect    // если в родителе по ключу элемента есть группа, можно его расположить после указанного элемента из группы
     * }
     * */
    setParent(config = null) {
        this.dropParent();

        if (config === null) return null;

        var parent = null,
            next = null;
        if (config instanceof lx.Rect) {
            parent = config;
            config = {};
        } else {
            if (config.parent === null) return null;

            if (config.before && config.before.parent) {
                parent = config.before.parent;
                next = config.before;
            } else if (config.after && config.after.parent) {
                parent = config.after.parent;
                next = config.after.nextSibling();
            } else {
                parent = config.parent || lx.WidgetHelper.autoParent;
            }
        }
        if (!parent) return null;

        config.nextSibling = next;
        parent.addChild(this, config);
        return parent;
    }

    dropParent() {
        if (this.parent) this.parent.remove(this);
        this.parent = null;
        return this;
    }

    after(el) {
        return this.setParent({ after: el });
    }

    before(el) {
        return this.setParent({ before: el });
    }

    del() {
        //TODO - проверить важность этой строчки. После обертки над DOM-элементом, он может не существовать, а обертка да
        // if (!this.getDomElem()) return;
        var p = this.parent;
        if (p) return p.del(this);
        // Если нет родителя - это корневой элемент, его не удаляем
        return 0;
    }

    /**
     * Назначить элементу поле, за которым он сможет следить
     * */
    #lx:client setField(name, func, type = null) {
        this._field = name;
        this._bindType = type || lx.Binder.BIND_TYPE_FULL;

        if (func) {
            var valFunc = this.lxHasMethod('value')
                ? this.value
                : function(val) { if (val===undefined) return this._val; this._val = val; };
            this.innerValue = valFunc;

            // Определяем - может ли переданная функция возвращать значение (кроме как устанавливать)
            var str = func.toString(),
                argName = (str[0] == '(')
                    ? str.match(/^\((.*?)(?:,|\))/)[1]
                    : str.match(/(?:^([\w\d_]+?)=>|^function\s*\((.*?)(?:,|\)))/)[1],
                reg = new RegExp('if\\s*\\(\\s*' + argName + '\\s*===\\s*undefined'),
                isCallable = (str.match(reg) !== null);

            /* Метод, через который осуществляется связь с моделью:
             * - модель отсюда читает значение, когда на виджете триггерится событие 'change'
             * - модель сюда записывает значение поля, когда оно меняется через сеттер
             */
            this.value = function(val) {
                if (val === undefined) {
                    if (isCallable) return func.call(this);
                    return this.innerValue();
                }
                var oldVal = isCallable ? func.call(this) : this.innerValue();
                /* Важно:
                 * - цепочка алгоритма может быть такая:
                 * 1. В пользовательском коде вызван метод .value(val), передано какое-то значение
                 * 2. Значение новое - оно присваивается значению виджета, триггерится событие 'change'
                 * 3. На 'change' срабатывает актуализация модели - в ней через сеттер записывается новое значение
                 * 4. В сеттере модели тоже есть логика актуализации - на этот раз виджета, через метод value(val)
                 * 5. Этот метод будет вызван повторно, чтобы не попасть в рекурсию - при попытке присвоить значние, идентичное текущему,
                 *    ничего не делаем - логично, если значение x "поменялось" на значение x, изменений не произошло - событие 'change' не должно триггериться
                 */
                if (lx.CompareHelper.deepCompare(val, oldVal)) return;
                this.innerValue(val);
                func.call(this, val, oldVal);
                this.trigger('change', val, oldVal);
            };
        }

        return this;
    }
    /* 5. Environment managment */
    //==================================================================================================================


    //==================================================================================================================
    /* 6. Environment navigation */
    nextSibling() {
        return this.domElem.nextSibling();
    }

    prevSibling() {
        return this.domElem.prevSibling();
    }

    /**
     * Поиск первого ближайшего предка, удовлетворяющего условию из переданной конфигурации:
     * 1. is - точное соответствие переданному конструктору или объекту
     * 2. hasProperty|hasProperties - имеет свойство(ва), при передаче значений проверяется их соответствие
     * 3. checkMethods - имеет метод(ы), проверяются возвращаемые ими значения
     * 4. instance - соответствие инстансу (отличие от 1 - может быть наследником инстанса)
     * */
    ancestor(info={}) {
        if (info.hasProperty) info.hasProperties = [info.hasProperty];
        var p = this.parent;
        while (p) {
            if (info.isFunction) {
                if (info(p)) return p;
            } else {
                if (info.is) {
                    var instances = info.is.isArray ? info.is : [info.is];
                    for (var i=0, l=instances.len; i<l; i++) {
                        if (p.constructor === instances[i] || p === instances[i])
                            return p;
                    }
                }

                if (info.hasProperties) {
                    var prop = info.hasProperties;
                    if (prop.isObject) {
                        var match = true;
                        for (var name in prop)
                            if (!(name in p) || prop[name] != p[name]) {
                                match = false;
                                break;
                            }
                        if (match) return p;
                    } else if (prop.isArray) {
                        var match = true;
                        for (var j=0, l=prop.len; j<l; j++)
                            if (!(prop[j] in p)) {
                                match = false;
                                break;
                            }
                        if (match) return p;
                    }
                }

                if (info.checkMethods) {
                    var match = true;
                    for (var name in info.checkMethods) {
                        if (!(name in p) || !p[name].isFunction || p[name]() != info.checkMethods[name]) {
                            match = false;
                            break;
                        }
                    }
                    if (match) return p;
                }

                if (info.instance && p instanceof info.instance) return p;
            }

            p = p.parent;
        }
        return null;
    }

    /**
     * Определяет имеет ли элемент данного предка
     * */
    hasAncestor(box) {
        var temp = this.parent
        while (temp) {
            if (temp === box) return true;
            temp = temp.parent;
        }
        return false;
    }

    /**
     * Поиск подымается по иерархии родителей, вернется первый ближайший найденный элемент
     * */
    neighbor(key) {
        var parent = this.parent;
        while (parent) {
            var el = parent.get(key);
            if (el) return el;
            parent = parent.parent;
        }
        return null;
    }

    /*
     * Родительский сниппет для данного виджета
     * */
    getSnippet() {
        #lx:server { return Snippet; }
        return this.ancestor({hasProperty: 'isSnippet'});
    }

    /*
     * Сниппет с выходом на плагин, он же корневой сниппет в данном плагине
     * */
    rootSnippet() {
        #lx:server { return Snippet; }
        if (this.plugin) return this;
        return this.ancestor({hasProperty: 'plugin'});
    }

    getPlugin() {
        #lx:server { return Plugin; }
        var root = this.rootSnippet();
        if (!root) return null;
        return root.plugin;
    }
    /* 6. Environment navigation */
    //==================================================================================================================


    //==================================================================================================================
    /* 7. Events */
    on(eventName, func, useCapture) {
        // useCapture = useCapture || false;
        // useCapture в ie не обрабатывается, поэтому не реализован

        if (!func) return this;

        //todo
        if (eventName == 'mousedown') this.on('touchstart', func, useCapture);
        else if (eventName == 'mousemove') this.on('touchmove', func, useCapture);
        else if (eventName == 'mouseup' /*|| eventName == 'click'*/) this.on('touchend', func, useCapture);

        if (func.isString)
            func = this.unpackFunction(func);

        if (func) this.domElem.addEvent(eventName, func);
        return this;
    }

    off(eventName, func, useCapture) {
        // useCapture = useCapture || false;
        // useCapture в ie не обрабатывается, поэтому не реализован

        this.domElem.delEvent(eventName, func);
        return this;
    }

    hasTrigger(type, func) {
        return this.domElem.hasEvent(type, func);
    }

    move(config={}) {
        if (config === false) {
            this.off('mousedown', lx.move);
            return;
        }

        if (config.parentMove && config.parentResize) delete config.parentMove;
        if (this.moveParams === undefined) this.moveParams = {};
        this.moveParams = {
            xMove        : lx.getFirstDefined(this.moveParams.xMove, config.xMove, true),
            yMove        : lx.getFirstDefined(this.moveParams.yMove, config.yMove, true),
            parentMove   : lx.getFirstDefined(this.moveParams.parentMove, config.parentMove, false),
            parentResize : lx.getFirstDefined(this.moveParams.parentResize, config.parentResize, false),
            xLimit       : lx.getFirstDefined(this.moveParams.xLimit, config.xLimit, true),
            yLimit       : lx.getFirstDefined(this.moveParams.yLimit, config.yLimit, true),
            moveStep     : lx.getFirstDefined(this.moveParams.moveStep, config.moveStep, 1),
            locked       : false
        };
        #lx:client{ if (!this.hasTrigger('mousedown', lx.move)) this.on('mousedown', lx.move); }
        #lx:server{ this.onload('()=>this.on(\'mousedown\', lx.move);'); }
        return this;
    }

    lockMove() {
        if (!this.moveParams) return;
        this.moveParams.locked = true;
    }

    unlockMove() {
        if (!this.moveParams) return;
        this.moveParams.locked = false;
    }

    click(func) { this.on('click', func); return this; }

    display(func) { this.on('display', func); return this; }

    displayIn(func) { this.on('displayin', func); return this; }

    displayOut(func) { this.on('displayout', func); return this; }

    displayOnce(func) {
        #lx:server {
            this.onload('.displayOnce', func);
        };

        #lx:client {
            if (func.isString) func = this.unpackFunction(func);
            if (!func) return this;
            var f;
            f = function() {
                func.call(this);
                this.off('displayin', f);
            };
            this.on('displayin', f);
        };

        return this;
    }

    triggerDisplay(event) {
        if (!this.isDisplay()) {
            if (this.displayNow) {
                this.trigger('displayout', event);
                this.displayNow = false;
            }
        } else {
            if (!this.displayNow) {
                this.displayNow = true;
                this.trigger('displayin', event);
            } else this.trigger('display', event);
        }
    }

    copyEvents(el) {
        if (!el) return this;

        this.off();

        var eventList = el.domElem.getEvents();
        if (!eventList) return this;

        for (var eventName in eventList) {
            var events = eventList[eventName];
            for (var i in events) {
                this.on(eventName, events[i]);
            }
        }

        return this;
    }

    trigger(eventName, ...args) {#lx:client{
        var elem = this.getDomElem();
        if (!elem) return;
        if ( this.disabled() || !elem.events || !(eventName in elem.events) ) return;

        var res;
        if (eventName == 'blur') {
            elem.blur();
            res = true;
        } else {
            res = [];
            for (var i in elem.events[eventName]) {
                var func = elem.events[eventName][i];
                res.push( func.apply(this, args) );
            }
        }

        if (res.isArray && res.length == 1) res = res[0];
        return res;
    }}

    #lx:server {
        /**
         * Просто делегирование выполнения метода на JS
         * */
        onload(handler, args = null) {
            if (!this.forOnload) this.forOnload = [];
            this.forOnload.push(args ? [handler, args] : handler);
            return this;
        }

        onpostunpack(handler) {
            switch (App.getSetting('unpackType')) {
                case lx.Application.POSTUNPACK_TYPE_IMMEDIATLY:
                    this.onload(handler);
                    break;
                case lx.Application.POSTUNPACK_TYPE_FIRST_DISPLAY:
                    this.displayOnce(handler);
                    break;
                case lx.Application.POSTUNPACK_TYPE_ALL_DISPLAY:
                    this.displayIn(handler);
                    break;
            }
        }
    }
    /* 7. Events */
    //==================================================================================================================


    //==================================================================================================================
    /* 8. Load */
    #lx:server {
        /**
         * Пакуем код функуии в строку, приводя к формату:
         * '(arg1, arg2) => ...function code'
         * Метод, обратный клиентскому [[unpackFunction(str)]]
         * */
        packFunction(func) {
            return lx.functionToString(func);
        }
    }

    #lx:client {
        /**
         * Если при распаковке хэндлеры обработчиков событий перенаправляют на реальные функции
         * Варианты аргументов:
         * - ('.funcName')     // будет искать функцию 'funcName' среди своих методов
         * - ('::funcName')    // будет искать функцию 'funcName' среди статических методов своего класса
         * - ('lx.funcName')    // будет искать функцию 'funcName' в листе функций lx
         * */
        findFunction(handler) {
            // '.funcName'
            if (handler.match(/^\./)) {
                var func = this[handler.split('.')[1]];
                if (!func || !func.isFunction) return null;
                return func;
            }

            // '::funcName'
            if (handler.match(/^::/)) {
                var func = lx[this.lxClassName][handler.split('::')[1]];
                if (!func || !func.isFunction) return null;
                return func;
            }

            // 'lx.funcName'
            if (handler.match(/^lx\./)) {
                return lx.getHandler(handler.split('.')[1]);
            }

            // Если нет явного префикса - плохо, но попробуем от частного к общему найти хэндлер
            var f = null;
            f = this.findFunction('.' + handler);
            if (f) return f;
            f = this.findFunction('::' + handler);
            if (f) return f;
            f = this.findFunction('lx.' + handler);
            if (f) return f;

            return null;
        }

        /**
         * Формат функции, которую упаковали в строку на стороне сервера:
         * '(arg1, arg2) => ...function code'
         * Метод, обратный серверному [[packFunction(func)]]
         * */
        unpackFunction(str) {
            var f = null;
            if (str.match(/^\(.*?\)\s*=>/)) {
                str = str.replace(
                    /^(\(.*?\)\s*=>\s*{?}?)/,
                    '$1const Plugin=this.getPlugin();const Snippet=this.getSnippet(); '
                );
                f = lx.stringToFunction(str);
            } else f = this.findFunction(str);
            if (!f) return null;
            return f;
        }

        /**
         * Распаковка приоритетов геометрических параметров
         * */
        unpackGeom() {
            var val = this.geom,
                arr = val.split('|');
            this.geom = {};
            if (arr[0] != '') {
                var params = arr[0].split(',');
                this.geomPriorityH(+params[0], +params[1]);
            }
            if (arr[1] != '') {
                var params = arr[1].split(',');
                this.geomPriorityV(+params[0], +params[1]);
            }
        }

        /**
         * Распаковка расширенных свойств
         * */
        unpackProperties() {
            if (!this.inLoad) return;

            if (this.geom && this.geom.isString) this.unpackGeom();

            // Стратегии позиционирования
            if (this.__ps) {
                var ps = this.lxExtract('__ps').split(';'),
                    psName = ps.shift(),
                    con = lx.getClassConstructor(psName);

                this.positioningStrategy = new con(this);
                this.positioningStrategy.unpack(ps);
            }

            // Функции-обработчики событий
            if (this.handlers) {
                for (var i in this.handlers) for (var j in this.handlers[i]) {
                    var f = this.unpackFunction(this.handlers[i][j]);
                    if (f) this.on(i, f);
                }

                delete this.handlers;
            }
        }

        /**
         * Метод чисто для допиливания виджета при получении данных от сервера
         * */
        postUnpack(config={}) {
            // pass
        }

        /**
         * Метод, вызываемый загрузчиком для допиливания элемента
         * */
        postLoad() {
            var config = this.lxExtract('__postBuild') || {};
            this.postUnpack(config);
            this.postBuild(config);
        }

        restoreLinks(loader) {
            // pass
        }

        /**
         * @param key - ключ вызываемого метода
         * @param params - параметры, с которыми нужно вызвать метод
         * */
        static ajax(key, params = []) {
            return new lx.WidgetRequest(this.lxFullName, key, params);
        }
    }
    /* 8. Load */
    //==================================================================================================================
}
