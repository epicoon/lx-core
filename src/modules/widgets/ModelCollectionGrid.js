#lx:module lx.ModelCollectionGrid;

#lx:use lx.Box;
#lx:use lx.Paginator;
#lx:use lx.Input;
#lx:use lx.Checkbox;
#lx:use lx.Scroll;

class ModelCollectionGrid extends lx.Box #lx:namespace lx {
    #lx:const
        DEFAULT_COLUMN_WIDTH = '200px';

    getBasicCss() {
        return {
            main: 'lx-MCG',
            lPart: 'lx-MCG-lPart',
            head: 'lx-MCG-head',
            rowBack: 'lx-MCG-rowBack'
        }
    }
    
    static initCssAsset(css) {
        css.inheritClass('lx-MCG', 'AbstractBox');
        css.addClass('lx-MCG-lPart', {
            borderRight: 'thick double ' + css.preset.widgetBorderColor
        });
        css.addClass('lx-MCG-head', {
            backgroundColor: css.preset.altMainBackgroundColor,
            borderBottom: 'thick double ' + css.preset.widgetBorderColor
        });
        css.addClass('lx-MCG-rowBack', {
            backgroundColor: css.preset.altMainBackgroundColor
        });
    }

    build(config) {
        this.totalCount = null;
        this.collection = null;
        this.columnSequence = [];
        this.lockedColumn = null;
        this.columnModifiers = {};

        if (config.paginator === undefined)
            config.paginator = true;

        this.streamProportional({indent:'5px', direction: lx.VERTICAL});

        //TODO
        if (config.header) this.add(lx.Box, {key: 'header'});
        __buildWrapper(this, config);
        if (config.paginator) this.add(lx.Paginator, {key: 'paginator', height: '40px'});
        //TODO
        if (config.footer) this.add(lx.Box, {key: 'footer'});
    }

    #lx:client clientBuild(config) {
        super.clientBuild(config);

        const rBody = this->>rBody;
        const lBody = this->>lBody;
        rBody.on('scroll', ()=>{
            this->>rBack.scrollTo({ y: rBody.getScrollPos().y });
            this->>lBack.scrollTo({ y: rBody.getScrollPos().y });
            this->>lBody.scrollTo({ y: rBody.getScrollPos().y });
            this->>rHead.scrollTo({ x: rBody.getScrollPos().x });
        });
        lBody.on('wheel', e=>rBody.get('scrollV').moveTo(lBody.getScrollPos().y + e.deltaY));
    }

    setTotalCount(count) {
        this.totalCount = count;
    }

    setCollection(collection) {
        this.collection = collection;
        if (this.totalCount === null)
            this.totalCount = this.collection.len;
    }

    dropCollection() {
        if (!this.collection) return;

        this->>lBackStream.dropMatrix();
        this->>lStream.dropMatrix();
        this->>rBackStream.dropMatrix();
        this->>rStream.dropMatrix();
        this->>lBackStream.clear();
        this->>lStream.clear();
        this->>rBackStream.clear();
        this->>rStream.clear();

        this->>lHeadStream.clear();
        this->>rHeadStream.clear();

        this->>lPart.width(0);

        this.totalCount = null;
        this.collection = null;
        this.columnSequence = [];
        this.lockedColumn = null;
        this.columnModifiers = {};

        this->paginator.setElementsCount(0);
    }

    setLockedColumn(columnName) {
        this.lockedColumn = columnName;
    }

    getColumnSequence() {
        if (this.columnSequence.len === 0) {
            if (this.collection === null)
                throw 'The grid must have a collection';

            this.columnSequence = this.collection.modelClass.schema.getFieldNames();
        }

        return this.columnSequence;
    }

    setColumnSequence(sequence) {
        this.columnSequence = sequence;
    }

    addColumn(config) {
        const columnName = config.name || null;
        if (!columnName)
            throw 'New colunm require name';

        let sequence = this.getColumnSequence();
        if (config.before) {
            let index = sequence.indexOf(config.before);
            if (index === -1)
                throw 'The grid\'s collection doesn\'t have column ' + config.before;
            sequence.splice(index, 0, columnName);
        } else
            sequence.push(columnName);
        this.setColumnSequence(sequence);

        this.modifyColumn(columnName, config);
    }

    modifyColumn(columnName, config) {
        let modifier = this.getColumnModifier(columnName);
        if (config.definition !== undefined)
            modifier.definition = config.definition;
        if (config.widget !== undefined)
            modifier.widget = config.widget;
        if (config.render !== undefined)
            modifier.render = config.render;
        modifier.title = lx.getFirstDefined(config.title, columnName);
        this.columnModifiers[columnName] = modifier;
    }

    getColumnModifier(columnName) {
        let modifier = this.columnModifiers[columnName] || {};

        if (modifier.definition === undefined)
            modifier.definition = __getFieldDefinition(this, columnName);

        if (modifier.widget === undefined)
            modifier.widget = __getDefaultColumnWidget(modifier.definition.type);
        if (modifier.widget.width === undefined)
            modifier.widget.width = self::DEFAULT_COLUMN_WIDTH;

        if (modifier.render === undefined)
            modifier.render = __getDefaultColumnRender(modifier.definition.type);

        if (modifier.title === undefined)
            modifier.title = columnName;

        return modifier;
    }

    rowAddClass(rowIndex, cssClass) {
        const lRow = this->>lBackStream.child(rowIndex);
        lRow.addClass(cssClass);
        const rRow = this->>rBackStream.child(rowIndex);
        rRow.addClass(cssClass);
    }

    rowRemoveClass(rowIndex, cssClass) {
        const lRow = this->>lBackStream.child(rowIndex);
        lRow.removeClass(cssClass);
        const rRow = this->>rBackStream.child(rowIndex);
        rRow.removeClass(cssClass);
    }

    getCell(columnKey, rowIndex) {
        const lRow = this->>lStream.child(rowIndex);
        if (lRow.contains(columnKey))
            return lRow.get(columnKey);

        const rRow = this->>rStream.child(rowIndex);
        return rRow.get(columnKey);
    }

    render() {
        if (!this.collection) return;

        const _t = this;
        const schema = this.collection.modelClass.schema;
        const sequence = this.getColumnSequence();
        const unlockedIndex = (this.lockedColumn)
            ? sequence.indexOf(this.lockedColumn) + 1
            : 0;
        const lHeadStream = this->>lHeadStream;
        const rHeadStream = this->>rHeadStream;

        // Left part
        let lockWidth = [];
        for (let i=0, l=unlockedIndex; i<l; i++) {
            let fieldName = sequence[i],
                columnModifier = this.getColumnModifier(fieldName);
            lockWidth.push(columnModifier.widget.width);
            const title = lHeadStream.add(lx.Box, {
                width: columnModifier.widget.width,
                text: columnModifier.title,
                css: 'lx-ellipsis'
            });
            title.align(lx.CENTER, lx.MIDDLE);
        }
        lockWidth = lx.Geom.calculate('+', ...lockWidth);
        this->>lPart.width(lockWidth);
        this->>lBackStream.matrix({
            items: this.collection,
            itemBox: [lx.Box, {css: this.basicCss.rowBack}]
        });
        this->>lStream.matrix({
            items: this.collection,
            itemBox: [lx.Box, {stream: {direction: lx.HORIZONTAL}}],
            itemRender: function (row, model) {
                for (let i=0, l=unlockedIndex; i<l; i++) {
                    let fieldName = sequence[i],
                        columnModifier = _t.getColumnModifier(fieldName);
                    let config = {width: columnModifier.widget.width, css: 'lx-ellipsis'};
                    if (schema.hasField(fieldName))
                        config.field = fieldName;
                    else config.key = fieldName;
                    const box = new lx.Box(config);
                    box.align(lx.CENTER, lx.MIDDLE);
                    if (columnModifier.render)
                        columnModifier.render(box, model);
                }

                row.click(function (e) {
                    _t.trigger('rowClick', e, this.index);
                });
            }
        });

        // Right part
        for (let i=unlockedIndex, l=sequence.len; i<l; i++) {
            let fieldName = sequence[i],
                columnModifier = this.getColumnModifier(fieldName);
            const title = rHeadStream.add(lx.Box, {
                width: columnModifier.widget.width,
                text: columnModifier.title,
                css: 'lx-ellipsis'
            });
            title.align(lx.LEFT, lx.MIDDLE);
        }
        this->>rBackStream.matrix({
            items: this.collection,
            itemBox: [lx.Box, {css: this.basicCss.rowBack}]
        });
        this->>rStream.matrix({
            items: this.collection,
            itemBox: [lx.Box, {stream: {direction: lx.HORIZONTAL}}],
            itemRender: function (row, model) {
                for (let i=unlockedIndex, l=sequence.len; i<l; i++) {
                    let fieldName = sequence[i],
                        columnModifier = _t.getColumnModifier(fieldName);
                    let config = {width: columnModifier.widget.width, css: 'lx-ellipsis'};
                    if (schema.hasField(fieldName))
                        config.field = fieldName;
                    else config.key = fieldName;
                    const box = new lx.Box(config);
                    box.align(lx.LEFT, lx.MIDDLE);
                    if (columnModifier.render)
                        columnModifier.render(box, model);
                }

                row.click(function (e) {
                    _t.trigger('rowClick', e, this.index);
                });
            }
        });

        this->paginator.setElementsCount(this.totalCount);
    }
}

function __buildWrapper(self, wrapper, config) {
    const gridWrapper = self.add(lx.Box, {key: 'wrapper'});
    gridWrapper.streamProportional({direction: lx.HORIZONTAL});
    gridWrapper.begin();

    let streamConfig = {indent: '5px'};

    const lPart = new lx.Box({
        key: 'lPart',
        width: '0px',
        css: self.basicCss.lPart
    });
    lPart.style('min-width', '0px');

    const lHead = lPart.add(lx.Box, {
        key: 'lHead',
        geom: [0, 0, 100, '50px'],
        css: self.basicCss.head
    });
    const lBack = lPart.add(lx.Box, {
        key: 'lBack',
        geom: [0, '50px', null, null, 0, 0]
    });
    const lBody = lPart.add(lx.Box, {
        key: 'lBody',
        geom: [0, '50px', null, null, 0, 0]
    });
    lHead.add(lx.Box, {key: 'lHeadStream', height: '100%', stream: {direction: lx.HORIZONTAL}});
    lBack.overflow('hidden');
    lBody.overflow('hidden');

    const lBackStream = lBack.add(lx.Box, {key: 'lBackStream'});
    lBackStream.stream(streamConfig);
    const lStream = lBody.add(lx.Box, {key: 'lStream'});
    lStream.stream(streamConfig);


    const rPart = new lx.Box({key: 'rPart'});

    const rHead = rPart.add(lx.Box, {
        key: 'rHead',
        geom: [0, 0, 100, '50px'],
        css: self.basicCss.head
    });
    const rBack = rPart.add(lx.Box, {
        key: 'rBack',
        geom: [0, '50px', null, null, 0, 0]
    });
    const rBody = rPart.add(lx.Box, {
        key: 'rBody',
        geom: [0, '50px', null, null, 0, 0]
    });
    rHead.add(lx.Box, {key: 'rHeadStream', height: '100%', stream: {direction: lx.HORIZONTAL}});
    rHead.overflow('hidden');
    rBack.overflow('hidden');
    rBody.addContainer();
    rBody.addStructure(lx.Scroll, {key:'scrollV', type: lx.VERTICAL});
    rBody.addStructure(lx.Scroll, {key:'scrollH', type: lx.HORIZONTAL});

    const rBackStream = rBack.add(lx.Box, {key: 'rBackStream'});
    rBackStream.stream(streamConfig);
    const rStream = rBody.add(lx.Box, {key: 'rStream'});
    rStream.stream(streamConfig);

    gridWrapper.end();
}

function __getFieldDefinition(self, fieldName) {
    const schema = self.collection.modelClass.schema;
    let fieldData = schema.hasField(fieldName) ? schema.getField(fieldName) : {};
    if (!fieldData.type)
        fieldData.type = lx.ModelTypeEnum.STRING;
    return fieldData;
}

function __getDefaultColumnWidget(type)
{
    switch (type) {

        //TODO - старый способ представления первичного ключа на фронте. Требует рефакторинга
        case 'pk':

        case lx.ModelTypeEnum.INTEGER:
        case lx.ModelTypeEnum.BOOLEAN:
            return {width: '100px'};
        case lx.ModelTypeEnum.STRING:
            return {width: '200px'};
        default:
            return {width: '200px'};
    }
}

function __getDefaultColumnRender(type)
{
    switch (type) {
        case lx.ModelTypeEnum.INTEGER:
        case lx.ModelTypeEnum.BOOLEAN:
        case lx.ModelTypeEnum.STRING:
            return null;
        default:
            return null;
    }
}
