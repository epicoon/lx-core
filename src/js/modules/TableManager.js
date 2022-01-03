#lx:module lx.TableManager;

/* Таблица начинает генерировать события:
 * selectionChange (event, oldCell, newCell)
 * rowAdded (event)
 * cellChange (event, cell)
 * */
lx.TableManager = {
    isActive: false,
    tables: [],
    activeTable: null,
    activeCell: null,

    tableCss: 'lx-TM-table',
    rowCss: 'lx-TM-row',
    cellCss: 'lx-TM-cell',

    cellsForSelect: false,  // при анселекте таблицы выбранная на этот момент ячейка запоминается и продолжает подсвечиваться
    autoRowAdding: false,   // разрешается автодобавление строк при движении курсора вниз с последней строки
    cellEnterEnable: true,  // разрешается ввод текста в ячейки

    register: function(table, config = {}) {
        if (!(table instanceof lx.Table) || this.tables.includes(table)) return;
        if (this.tables.lxEmpty()) this.start();
        this.tables.push(table);

        table.__interactiveInfo = {
            cellsForSelect: config.cellsForSelect || lx.TableManager.cellsForSelect,
            autoRowAdding: config.autoRowAdding || lx.TableManager.autoRowAdding,
            cellEnterEnable: config.cellEnterEnable || lx.TableManager.cellEnterEnable
        };

        table.activeRow = function() {
            if (!this.interactiveInfo || !this.interactiveInfo.cellsForSelect) return null;
            if (this.interactiveInfo.row === undefined) return null;
            return this.interactiveInfo.row;
        }

        table.activeCell = function() {
            if (!this.interactiveInfo || !this.interactiveInfo.cellsForSelect) return null;
            if (this.interactiveInfo.cell === undefined) return null;
            return this.interactiveInfo.cell;
        }

        table.on('click', [this, this.click]);
    },

    unregister: function(tab) {
        if (this.tables.lxEmpty()) return;
        var index = this.tables.indexOf(tab);
        if (index != -1) {
            var table = this.tables[index];
            delete table.__interactiveInfo;
            delete table.activeCell;
            delete table.activeRow;
            this.activeTable.off('click', this.click);
            this.tables.splice(index, 1);
        }
        if (this.tables.lxEmpty()) this.stop();
    },

    start: function() {
        if (this.isActive) return;
        lx.on('keydown', [this, this.keyDown]);
        lx.on('keyup', [this, this.keyUp]);
        lx.on('mouseup', [this, this.outclick]);
        this.isActive = true;
    },

    stop: function() {
        if (!this.isActive) return;
        this.unselect();
        lx.off('keydown', this.keyDown);
        lx.off('keyup', this.keyUp);
        lx.off('mouseup', this.outclick);
        this.isActive = false;
    },

    unselect: function() {
        if (!this.activeTable) return;

        this.activeTable.trigger('selectionChange', event, this.activeCell, null);

        this.removeClasses(this.activeTable, this.activeCell);
        this.activeCell = null;
        this.activeTable = null;
    },

    applyClasses: function(tab, cell) {
        tab.addClass(this.tableCss);
        if (cell) cell.addClass(this.cellCss);
    },

    removeClasses: function(tab, cell) {
        tab.removeClass(this.tableCss);
        if (cell && !tab.__interactiveInfo.cellsForSelect)
            cell.removeClass(this.cellCss);
    },

    actualizeCellClass: function(oldCell, newCell) {
        if (oldCell != undefined) oldCell.removeClass(this.cellCss);
        if (newCell != undefined) newCell.addClass(this.cellCss);
    },

    toUp: function(event) {
        var cell = this.activeCell;
        if ( cell.contains('input') ) return;

        var tab = this.activeTable,
            coords = cell.indexes(),
            rowNum = coords[0],
            colNum = coords[1];

        if ( !rowNum ) return;

        var newRow = tab.row(rowNum - 1),
            newCell = tab.cell(rowNum - 1, colNum);

        if (tab.__interactiveInfo.cellsForSelect)
            tab.__interactiveInfo.row = rowNum - 1;
        this.activeCell = newCell;

        this.actualizeCellClass(cell, newCell);

        var scr = tab.getDomElem().scrollTop,
            rT = newRow.getDomElem().offsetTop;
        if ( rT < scr ) tab.getDomElem().scrollTop = rT;

        tab.trigger('selectionChange', event, cell, newCell);
    },

    toDown: function(event) {
        var cell = this.activeCell;
        if ( cell.contains('input') ) return;

        var tab = this.activeTable,
            coords = cell.indexes(),
            rowNum = coords[0],
            colNum = coords[1];

        if ( tab.rowsCount() == rowNum + 1 ) {
            if (tab.__interactiveInfo.autoRowAdding) {
                tab.addRow();
                tab.trigger('rowAdded', event);
            } else return;
        }

        var newRow = tab.row( rowNum + 1 ),
            newCell = tab.cell(rowNum + 1, colNum);

        if (tab.__interactiveInfo.cellsForSelect)
            tab.__interactiveInfo.row = rowNum + 1;
        this.activeCell = newCell;

        this.actualizeCellClass(cell, newCell);

        var scr = tab.getDomElem().scrollTop,
            h = tab.getDomElem().offsetHeight,
            rT = newRow.getDomElem().offsetTop,
            rH = newRow.getDomElem().offsetHeight;
        if ( rT + rH > scr + h ) tab.getDomElem().scrollTop = rT + rH - h;

        tab.trigger('selectionChange', event, cell, newCell);
    },

    toLeft: function(event) {
        var cell = this.activeCell;
        if ( cell.contains('input') ) return;

        var tab = this.activeTable,
            coords = cell.indexes(),
            rowNum = coords[0],
            colNum = coords[1];

        if ( !colNum ) return;

        var newCell = tab.cell(rowNum, colNum - 1);

        if (tab.__interactiveInfo.cellsForSelect)
            tab.__interactiveInfo.col = colNum - 1;
        this.activeCell = newCell;

        this.actualizeCellClass(cell, newCell);

        var scr = tab.getDomElem().scrollLeft,
            rL = newCell.getDomElem().offsetLeft;
        if ( rL < scr ) tab.getDomElem().scrollLeft = rL;

        tab.trigger('selectionChange', event, cell, newCell);
    },

    toRight: function(event) {
        var cell = this.activeCell;
        if ( cell.contains('input') ) return;

        var tab = this.activeTable,
            coords = cell.indexes(),
            rowNum = coords[0],
            colNum = coords[1];

        if ( tab.colsCount(rowNum) == colNum + 1 ) return;

        var newCell = tab.cell(rowNum, colNum + 1);

        if (tab.__interactiveInfo.cellsForSelect)
            tab.__interactiveInfo.col = colNum + 1;
        this.activeCell = newCell;

        this.actualizeCellClass(cell, newCell);

        var scr = tab.getDomElem().scrollLeft,
            w = tab.getDomElem().offsetWidth,
            rL = newCell.getDomElem().offsetLeft,
            rW = newCell.getDomElem().offsetWidth;
        if ( rL + rW > scr + w ) tab.getDomElem().scrollLeft = rL + rW - w;

        tab.trigger('selectionChange', event, cell, newCell);
    },

    enterCell: function(event) {
        var tab = this.activeTable,
            cell = this.activeCell;

        if (cell.contains('input')) {
            var inp = cell.get('input');
            inp.off('blur');
            var boof = inp.getDomElem().value;

            //todo - что тут вообще происходит? Эта функция может нужна еще где-то? Запрятал ее, потому что она была в lx., но там ей точно не место,
            // а используется пока что только тут.
            function getCaretPosition( element ) {
                if ( lx.environment.browser == 'ie' ) {
                    var sel = document.selection.createRange();
                    var clone = sel.duplicate();

                    sel.collapse( true );
                    clone.moveToElementText( element );
                    clone.setEndPoint( 'EndToEnd', sel );

                    // баг в IE. Должно быть без +1, но тогда неправильно определяет при многострочности
                    return clone.text.length + 1;
                } else if ( element.selectionStart ) {
                    return element.selectionStart;
                }
                return element.value.length - 1;
            };
            pos = getCaretPosition(inp.getDomElem())-1;
            boof = boof.substring(0, pos) + boof.substring(pos+1);

            cell.del('input');
            cell.text(boof);

            cell.show();
            cell.trigger('blur');

            tab.trigger('cellChange', event, cell);
        } else {
            if ( tab.__interactiveInfo.cellEnterEnable ) cell.entry();
        }
    },

    keyDown: function(event) {
        if (this.activeTable == null) return;
        event = event || window.event;
        var code = (event.charCode) ? event.charCode: event.keyCode;

        var inputOn = ( this.activeCell && this.activeCell.contains('input') );

        switch (code) {
            case 38: this.toUp(event);    if (!inputOn) event.preventDefault(); break;
            case 40: this.toDown(event);  if (!inputOn) event.preventDefault(); break;
            case 37: this.toLeft(event);  if (!inputOn) event.preventDefault(); break;
            case 39: this.toRight(event); if (!inputOn) event.preventDefault(); break;

            case 27: if (!inputOn) this.unselect(); break;
        }
    },

    keyUp: function(event) {
        if (this.activeTable == null) return;
        event = event || window.event;
        var code = (event.charCode) ? event.charCode: event.keyCode;
        if (code == 13) this.enterCell(event);
    },

    click: function(event) {
        event = event || window.event;
        //todo можно еще в родителях поискать

        var target = event.target.__lx;
        if (!target) return;

        var newCell = target.lxClassName() == 'TableCell'
            ? target
            : target.ancestor((ancestor)=>ancestor.lxClassName() == 'TableCell');
        if (!newCell) return;

        if (this.activeCell == newCell) {
            this.enterCell(event);
            return;
        }

        var lastTab = this.activeTable,
            lastCell = this.activeCell,
            newTab = newCell.table();

        if (lastTab && newTab == lastTab) this.actualizeCellClass(lastCell, newCell);
        else {
            if (lastTab) this.removeClasses( lastTab, lastCell );
            this.activeTable = newTab;
            this.applyClasses( newTab, newCell );
        }

        this.activeCell = newCell;
        if (newTab.__interactiveInfo.cellsForSelect) {
            var ac = newTab.activeCell();
            if (ac) ac.removeClass(this.cellCss);
            var coords = newCell.indexes();
            newTab.__interactiveInfo.row = coords[0];
            newTab.__interactiveInfo.col = coords[1];
        }

        newTab.trigger('selectionChange', event, lastCell, newCell);
    },

    outclick: function(event) {
        if (!this.activeTable) return;
        event = event || window.event;
        if ( this.activeTable.containGlobalPoint(event.clientX, event.clientY) ) return;
        this.unselect();
    }
};
