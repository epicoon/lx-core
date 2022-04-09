#lx:module lx.TableManager;

// При анселекте таблицы выбранная на этот момент ячейка запомнится и продолжит подсвечиваться
const DEFAULT_CELLS_FOR_SELECT = false;
// Разрешает автодобавление строк при движении курсора вниз с последней строки
const DEFAULT_AUTO_ROW_ADDING = false;
// Разрешает ввод текста в ячейки
const DEFAULT_CELL_ENTER_ENABLE = true;

let __isActive = false;
let __tables = [];
let __activeTable = null;
let __activeCell = null;

/* Таблица начинает генерировать события:
 * selectionChange
 * rowAdded
 */
#lx:namespace lx;
class TableManager extends lx.Module {
    static initCssAsset(css) {
        css.addClasses({
            'lx-TM-table': 'border: ' + css.preset.checkedDarkColor + ' solid 2px !important',
            'lx-TM-row': 'background-color: ' + css.preset.checkedMainColor + ' !important',
            'lx-TM-cell': 'background-color: ' + css.preset.checkedMainColor + ' !important'
        });
    }
    
    static register(table, config = {}) {
        if (!(table instanceof lx.Table) || __tables.includes(table)) return;
        if (__tables.lxEmpty()) this.start();
        __tables.push(table);

        table.__interactiveInfo = {
            cellsForSelect: lx.getFirstDefined(config.cellsForSelect, DEFAULT_CELLS_FOR_SELECT),
            autoRowAdding: lx.getFirstDefined(config.autoRowAdding, DEFAULT_AUTO_ROW_ADDING),
            cellEnterEnable: lx.getFirstDefined(config.cellEnterEnable, DEFAULT_CELL_ENTER_ENABLE)
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

        table.on('click', __handlerClick);
    }

    static unregister(tab) {
        if (__tables.lxEmpty()) return;
        var index = __tables.indexOf(tab);
        if (index != -1) {
            var table = __tables[index];
            delete table.__interactiveInfo;
            delete table.activeCell;
            delete table.activeRow;
            table.off('click', __handlerClick);
            __tables.splice(index, 1);
        }
        if (__tables.lxEmpty()) this.stop();
    }

    static start() {
        if (__isActive) return;
        lx.on('keydown', __handlerKeyDown);
        lx.on('keyup', __handlerKeyUp);
        lx.on('mouseup', __handlerOutclick);
        __isActive = true;
    }

    static stop() {
        if (!__isActive) return;
        this.unselect();
        lx.off('keydown', __handlerKeyDown);
        lx.off('keyup', __handlerKeyUp);
        lx.off('mouseup', __handlerOutclick);
        __isActive = false;
    }

    static unselect() {
        if (!__activeTable) return;

        __activeTable.trigger('selectionChange', __activeTable.newEvent({
            newCell: null,
            oldCell: __activeCell
        }));

        __removeClasses(__activeTable, __activeCell);
        __activeCell = null;
        __activeTable = null;
    }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Style
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
lx.TableManager.tableCss = 'lx-TM-table';
lx.TableManager.rowCss = 'lx-TM-row';
lx.TableManager.cellCss = 'lx-TM-cell';

function __applyClasses(tab, cell) {
    tab.addClass(lx.TableManager.tableCss);
    if (cell) cell.addClass(lx.TableManager.cellCss);
}

function __removeClasses(tab, cell) {
    tab.removeClass(lx.TableManager.tableCss);
    if (cell && !tab.__interactiveInfo.cellsForSelect)
        cell.removeClass(lx.TableManager.cellCss);
}

function __actualizeCellClass(oldCell, newCell) {
    if (oldCell != undefined) oldCell.removeClass(lx.TableManager.cellCss);
    if (newCell != undefined) newCell.addClass(lx.TableManager.cellCss);
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Handlers
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __handlerClick(event) {
    event = event || window.event;

    var target = event.target.__lx;
    if (!target) return;

    var newCell = target.lxClassName() == 'TableCell'
        ? target
        : target.ancestor((ancestor)=>ancestor.lxClassName() == 'TableCell');
    if (!newCell) return;

    if (__activeCell == newCell) {
        __enterCell(event);
        return;
    }

    var lastTab = __activeTable,
        lastCell = __activeCell,
        newTab = newCell.table();

    if (lastTab && newTab == lastTab) __actualizeCellClass(lastCell, newCell);
    else {
        if (lastTab) __removeClasses( lastTab, lastCell );
        __activeTable = newTab;
        __applyClasses( newTab, newCell );
    }

    __activeCell = newCell;
    if (newTab.__interactiveInfo.cellsForSelect) {
        var ac = newTab.activeCell();
        if (ac) ac.removeClass(lx.TableManager.cellCss);
        var coords = newCell.indexes();
        newTab.__interactiveInfo.row = coords[0];
        newTab.__interactiveInfo.col = coords[1];
    }

    event.newCell = newCell;
    event.oldCell = lastCell;
    newTab.trigger('selectionChange', event);
}

function __handlerOutclick(event) {
    if (!__activeTable) return;
    event = event || window.event;
    if ( __activeTable.containGlobalPoint(event.clientX, event.clientY) ) return;
    lx.TableManager.unselect();
}

function __handlerKeyDown(event) {
    if (__activeTable == null) return;
    event = event || window.event;
    var code = (event.charCode) ? event.charCode: event.keyCode;

    var inputOn = ( __activeCell && __activeCell.contains('input') );

    switch (code) {
        case 38: __toUp(event);    if (!inputOn) event.preventDefault(); break;
        case 40: __toDown(event);  if (!inputOn) event.preventDefault(); break;
        case 37: __toLeft(event);  if (!inputOn) event.preventDefault(); break;
        case 39: __toRight(event); if (!inputOn) event.preventDefault(); break;
    }
}

function __handlerKeyUp(event) {
    if (__activeTable == null) return;
    event = event || window.event;
    var code = (event.charCode) ? event.charCode: event.keyCode;
    if (code == 13) __enterCell(event);
    else if (code == 27) {
        let cell = __activeCell;
        if (cell.isEditing()) cell.blur();
    }
}

function __enterCell(event) {
    let tab = __activeTable;
    if (!tab || !tab.__interactiveInfo.cellEnterEnable) return;

    let cell = __activeCell;
    if (cell && !cell.isEditable()) {
        cell.setEditable(true);
        cell.on('blur', ()=>cell.setEditable(false));
        cell.edit();
    }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Move
 * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

function __toUp(event) {
    var cell = __activeCell;
    if ( cell.contains('input') ) return;

    var tab = __activeTable,
        coords = cell.indexes(),
        rowNum = coords[0],
        colNum = coords[1];

    if ( !rowNum ) return;

    var newRow = tab.row(rowNum - 1),
        newCell = tab.cell(rowNum - 1, colNum);

    if (tab.__interactiveInfo.cellsForSelect)
        tab.__interactiveInfo.row = rowNum - 1;
    __activeCell = newCell;

    __actualizeCellClass(cell, newCell);

    var scr = tab.getDomElem().scrollTop,
        rT = newRow.getDomElem().offsetTop;
    if ( rT < scr ) tab.getDomElem().scrollTop = rT;

    event = event || tab.newEvent();
    event.newCell = newCell;
    event.oldCell = cell;
    tab.trigger('selectionChange', event);
}

function __toDown(event) {
    var cell = __activeCell;
    if ( cell.contains('input') ) return;

    event = event || __activeTable.newEvent();

    var tab = __activeTable,
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
    __activeCell = newCell;

    __actualizeCellClass(cell, newCell);

    var scr = tab.getDomElem().scrollTop,
        h = tab.getDomElem().offsetHeight,
        rT = newRow.getDomElem().offsetTop,
        rH = newRow.getDomElem().offsetHeight;
    if ( rT + rH > scr + h ) tab.getDomElem().scrollTop = rT + rH - h;

    event.newCell = newCell;
    event.oldCell = cell;
    tab.trigger('selectionChange', event);
}

function __toLeft(event) {
    var cell = __activeCell;
    if ( cell.contains('input') ) return;

    var tab = __activeTable,
        coords = cell.indexes(),
        rowNum = coords[0],
        colNum = coords[1];

    if ( !colNum ) return;

    var newCell = tab.cell(rowNum, colNum - 1);

    if (tab.__interactiveInfo.cellsForSelect)
        tab.__interactiveInfo.col = colNum - 1;
    __activeCell = newCell;

    __actualizeCellClass(cell, newCell);

    var scr = tab.getDomElem().scrollLeft,
        rL = newCell.getDomElem().offsetLeft;
    if ( rL < scr ) tab.getDomElem().scrollLeft = rL;

    event = event || tab.newEvent();
    event.newCell = newCell;
    event.oldCell = cell;
    tab.trigger('selectionChange', event);
}

function __toRight(event) {
    var cell = __activeCell;
    if ( cell.contains('input') ) return;

    var tab = __activeTable,
        coords = cell.indexes(),
        rowNum = coords[0],
        colNum = coords[1];

    if ( tab.colsCount(rowNum) == colNum + 1 ) return;

    var newCell = tab.cell(rowNum, colNum + 1);

    if (tab.__interactiveInfo.cellsForSelect)
        tab.__interactiveInfo.col = colNum + 1;
    __activeCell = newCell;

    __actualizeCellClass(cell, newCell);

    var scr = tab.getDomElem().scrollLeft,
        w = tab.getDomElem().offsetWidth,
        rL = newCell.getDomElem().offsetLeft,
        rW = newCell.getDomElem().offsetWidth;
    if ( rL + rW > scr + w ) tab.getDomElem().scrollLeft = rL + rW - w;

    event = event || tab.newEvent();
    event.newCell = newCell;
    event.oldCell = cell;
    tab.trigger('selectionChange', event);
}
