#lx:module lx.ModelCollectionGrid;

#lx:use lx.Box;
#lx:use lx.Paginator;
#lx:use lx.Input;
#lx:use lx.Checkbox;

class ModelCollectionGrid extends lx.Box #lx:namespace lx {
    getBasicCss() {
        return {

        }
    }

    build(config) {
        if (config.paginator === undefined)
            config.paginator = true;

        this.streamProportional({indent:'5px'});

        //TODO
        if (config.header) {
            this.add(lx.Box, {key: 'header'});
        }

        var gridWrapper = this.add(lx.Box);
        gridWrapper.fill('white');
        // var grid = gridWrapper.

        if (config.paginator) {
            this.add(lx.Paginator, {key: 'paginator', height: '40px'});
        }

        //TODO
        if (config.header) {
            this.add(lx.Box, {key: 'footer'});
        }
    }




    render(collection) {
        var modelClass = collection.modelClass;
        var schema = modelClass.schema;
        console.log( schema );
        console.log( schema.getFieldTypes() );


        var colsSequence = ['id', 'isActive', 'name', 'score'];
        var lockColumn = 'isActive';
        var colConfigMap = {
            id: {
                mutable: false,
                renderModifier: null
            },
            name: {
                mutable: false,
                widget: lx.Box,
                renderModifier: function(widget) {
                    console.log('FIELD name RENDERING', widget);
                }
            },
            score: {
                mutable: true,
                renderModifier: null,
                width: '100px'
            },
            isActive: {
                mutable: true,
                renderModifier: null
            }
        };
        var rowModifier = function (row) {
            console.log('ROW RENDERING', row);
        }
        var customCols = {
            newFactor: {
                mutable: true,
                widget: lx.Checkbox,
                renderModifier: function (widget) {
                    console.log('CUSTOM FIELd newFactor RENDERING', widget);
                }
            }
        }




    }

}

