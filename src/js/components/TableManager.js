lx.TableManager = {
	tables: [],
	activeTable: null,
	activeCell: null,

	tableCss: 'lx-TM-table',
	rowCss: 'lx-TM-row',
	cellCss: 'lx-TM-cell',

	cellsForSelect: false,  // при анселекте таблицы выбранная на этот момент ячейка запоминается и продолжает подсвечиваться
	autoRowAdding: false,   // разрешается автодобавление строк при движении курсора вниз с последней строки
	cellEnterEnable: true,  // разрешается ввод текста в ячейки

	register: function(...args) {
		if (!args.length) return;
		args.each((a)=> this.registerTable(a));
	},

	registerTable: function(tab) {
		if (tab.className != 'Table' || this.tables.contain(tab)) return;

		if (this.tables.lxEmpty) this.start();

		this.tables.push(tab);
		if (!tab.interactiveInfo) tab.interactiveInfo = {
			cellsForSelect: this.cellsForSelect,
			autoRowAdding: this.autoRowAdding,
			cellEnterEnable: this.cellEnterEnable
		};

		tab.on('click', [this, this.click]);
	},

	unregisterTable: function(tab) {
		if (this.tables.lxEmpty) return;
		var index = this.tables.indexOf(tab);
		if (index != -1) {
			this.activeTable.off('click', this.click);
			this.tables.splice(index, 1);
		}
		if (this.tables.lxEmpty) this.stop();
	},

	start: function() {
		lx.on('keydown', [this, this.keyDown]);
		lx.on('keyup', [this, this.keyUp]);
		lx.on('mouseup', [this, this.outclick]);
	},
	
	stop: function() {
		this.unselect();
		this.tables = [];
		lx.off('keydown', this.keyDown);
		lx.off('keyup', this.keyUp);
		lx.off('mouseup', this.outclick);
	},

	unselect: function() {
		if (!this.activeTable) return;

		this.activeTable.trigger('selectionChange', event, this.activeCell);

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
		if (cell && !tab.interactiveInfo.cellsForSelect)
			cell.removeClass(this.cellCss);
	},

	actualizeCellClass: function(oldCell, newCell) {
		if (oldCell != undefined) oldCell.removeClass(this.cellCss);
		if (newCell != undefined) newCell.addClass(this.cellCss);
	},

	toUp: function(event) {
		var cell = this.activeCell;
		if ( cell.contain('input') ) return;

		var tab = this.activeTable,
			coords = cell.indexes(),
			rowNum = coords[0],
			colNum = coords[1];

		if ( !rowNum ) return;

		var newRow = tab.row(rowNum - 1),
			newCell = tab.cell(rowNum - 1, colNum);

		if (tab.interactiveInfo.cellsForSelect)
			tab.interactiveInfo.row = rowNum - 1;
		this.activeCell = newCell;

		this.actualizeCellClass(cell, newCell);

		var scr = tab.DOMelem.scrollTop,
			rT = newRow.DOMelem.offsetTop;
		if ( rT < scr ) tab.DOMelem.scrollTop = rT;

		tab.trigger('selectionChange', event, cell, newCell);
	},

	toDown: function(event) {
		var cell = this.activeCell;
		if ( cell.contain('input') ) return;

		var tab = this.activeTable,
			coords = cell.indexes(),
			rowNum = coords[0],
			colNum = coords[1];

		if ( tab.rowsCount() == rowNum + 1 ) {
			if (tab.interactiveInfo.autoRowAdding) {
				tab.addRow();
				tab.trigger('rowAdded', event);
			} else return;
		}

		var newRow = tab.row( rowNum + 1 ),
			newCell = tab.cell(rowNum + 1, colNum);

		if (tab.interactiveInfo.cellsForSelect)
			tab.interactiveInfo.row = rowNum + 1;
		this.activeCell = newCell;

		this.actualizeCellClass(cell, newCell);

		var scr = tab.DOMelem.scrollTop,
			h = tab.DOMelem.offsetHeight,
			rT = newRow.DOMelem.offsetTop,
			rH = newRow.DOMelem.offsetHeight;
		if ( rT + rH > scr + h ) tab.DOMelem.scrollTop = rT + rH - h;

		tab.trigger('selectionChange', event, cell, newCell);
	},

	toLeft: function(event) {
		var cell = this.activeCell;
		if ( cell.contain('input') ) return;

		var tab = this.activeTable,
			coords = cell.indexes(),
			rowNum = coords[0],
			colNum = coords[1];

		if ( !colNum ) return;

		var newCell = tab.cell(rowNum, colNum - 1);

		if (tab.interactiveInfo.cellsForSelect)
			tab.interactiveInfo.col = colNum - 1;
		this.activeCell = newCell;

		this.actualizeCellClass(cell, newCell);

		var scr = tab.DOMelem.scrollLeft,
			rL = newCell.DOMelem.offsetLeft;
		if ( rL < scr ) tab.DOMelem.scrollLeft = rL;

		tab.trigger('selectionChange', event, cell, newCell);
	},

	toRight: function(event) {
		var cell = this.activeCell;
		if ( cell.contain('input') ) return;

		var tab = this.activeTable,
			coords = cell.indexes(),
			rowNum = coords[0],
			colNum = coords[1];

		if ( tab.colsCount(rowNum) == colNum + 1 ) return;

		var newCell = tab.cell(rowNum, colNum + 1);

		if (tab.interactiveInfo.cellsForSelect)
			tab.interactiveInfo.col = colNum + 1;
		this.activeCell = newCell;

		this.actualizeCellClass(cell, newCell);

		var scr = tab.DOMelem.scrollLeft,
			w = tab.DOMelem.offsetWidth,
			rL = newCell.DOMelem.offsetLeft,
			rW = newCell.DOMelem.offsetWidth;
		if ( rL + rW > scr + w ) tab.DOMelem.scrollLeft = rL + rW - w;

		tab.trigger('selectionChange', event, cell, newCell);
	},

	enterCell: function(event) {
		var tab = this.activeTable,
			cell = this.activeCell;

		if (cell.contain('input')) {
			var inp = cell.get('input');
			inp.off('blur');
			var boof = inp.DOMelem.value;

			//todo - что тут вообще происходит? Эта функция может нужна еще где-то? Запрятал ее, потому что она была в lx., но там ей точно не место,
			// а используется пока что только тут.
			function getCaretPosition( element ) {
				if ( this.browserInfo.browser == 'ie' ) {
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
			pos = getCaretPosition(inp.DOMelem)-1;
			boof = boof.substring(0, pos) + boof.substring(pos+1);

			cell.del('input');
			cell.text(boof);

			lx.entryBlockId = '';
			cell.show();
			cell.trigger('blur');

			tab.trigger('cellChange', event, cell);
		} else {
			if ( tab.interactiveInfo.cellEnterEnable ) cell.entry();
		}
	},

	keyDown: function(event) {
		if (this.activeTable == null) return;
		event = event || window.event;
		var code = (event.charCode) ? event.charCode: event.keyCode;

		var inputOn = ( this.activeCell && this.activeCell.contain('input') );

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

		if (!event.target.lx) return;

		var newCell = event.target.lx.className == 'TableCell'
			? event.target.lx
			: event.target.lx.ancestor({hasProperties: {className: 'TableCell'}});
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
		if (newTab.interactiveInfo.cellsForSelect) {
			var ac = newTab.activeCell();
			if (ac) ac.removeClass(this.cellCss);
			var coords = newCell.indexes();
			newTab.interactiveInfo.row = coords[0];
			newTab.interactiveInfo.col = coords[1];
		}

		newTab.trigger('selectionChange', event, lastCell, newCell);
	},

	outclick: function(event) {
		if (this.activeTable == null) return;
		event = event || window.event;
		if ( this.activeTable.containPoint(event.clientX, event.clientY) ) return;
		this.unselect();
	}
};
