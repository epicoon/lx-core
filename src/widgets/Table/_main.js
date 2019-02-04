#use lx.Box as Box;

/* Special events:  // опосредованы работой TableManager
 * selectionChange
 * rowAdded
 * cellChange
 * */
class Table extends Box #in lx {
	/**
	 * config = {
	 * 	rows: integer
	 *	cols: integer
	 *	rowHeight: string | number
	 *	indents: {}
	 *	interactive: {} | boolean
	 * }
	 * */
	build(config) {
		super.build(config);

		var rows = config.rows || 0;
		this.cols = config.cols || 0;

		var beh;
		if (!this.getInnerSize(lx.HEIGHT)) beh = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_BY_CONTENT;
		else if (config.rowHeight) beh = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_SIMPLE;
		else if (rows) beh = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL;
		else {
			config.rowHeight = self::DEFAULT_ROW_HEIGHT;
			beh = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_SIMPLE;
		}

		this.indents = new lx.IndentData(config.indents || {});

		var indentData = this.indents.get(),
			rowStreamConfig = {sizeBehavior:beh};
		if (indentData.stepY) rowStreamConfig.stepY = indentData.stepY;
		if (indentData.paddingTop)    rowStreamConfig.paddingTop    = indentData.paddingTop;
		if (indentData.paddingBottom) rowStreamConfig.paddingBottom = indentData.paddingBottom;
		if (config.rowHeight) rowStreamConfig.defaultSize = config.rowHeight;
		this.stream(rowStreamConfig);

		if (!rows || !this.cols) return;

		this.insertRows(-1, rows);
	}

	postBuild(config) {
		super.postBuild();

		if (config.interactive && lx.TableManager) {
			this.interactiveInfo = {
				cellsForSelect: config.interactive.cellsForSelect || lx.TableManager.cellsForSelect,
				autoRowAdding: config.interactive.autoRowAdding || lx.TableManager.autoRowAdding,
				cellEnterEnable: config.interactive.cellEnterEnable || lx.TableManager.cellEnterEnable
			};
			lx.TableManager.register(this);
		}
	}

	row(row) {
		if (!this.children.r) return null;
		if (this.children.r instanceof lx.TableRow) return this.children.r;
		if (!this.children.r[row]) return null;
		return this.children.r[row];
	}

	cell(row, col) {
		var r = this.row(row);
		if (!r) return null;
		return r.cell(col);
	}

	rowsCount() {
		if (!this.children.r) return 0;
		if (this.children.r.isArray)
			return this.children.r.len;
		return 1;
	}

	colsCount(row) {
		if (row === undefined) return this.cols;
		if (!this.children.r
			|| !this.children.r[row]
			|| !this.children.r[row].children.c) return 0;
		if (this.children.r[row].children.c.isArray)
			return this.children.r[row].children.c.len;
		return 1;
	}

	rows(r0=0, r1) {
		var c = new lx.Collection();

		var rows = this.rowsCount();
		if (!rows) return c;
		if (r1 == undefined || r1 >= rows) r1 = rows - 1;

		if (r0 == 0 && r1 == rows - 1) return c.add(this.children.r);
	
		for (var i=r0; i<=r1; i++) c.add( this.row(i) );
		return c;
	}

	cells(r0=0, c0=0, r1, c1) {
		var c = new lx.Collection(),
			rows = this.rowsCount();

		if (r1 == undefined || r1 >= rows) r1 = rows - 1;

		for (var i=r0; i<=r1; i++) {
			var r = this.row(i),
				cols = this.colsCount(i);
			if (c1 == undefined || c1 >= cols) c1 = cols - 1;

			if (c0 == 0 && c1 == cols - 1) c.add(r.children.c);
			else {
				for (var j=c0; j<=c1; j++)
					c.add( this.cell(i, j) );
			}
		}

		return c;
	}

	/*
	 * Метод для перебора ячеек "в линию"
	 * для transpon==false (по умолчанию) по строкам, по колонкам
	 * для transpon==true по колонкам, по строкам
	 * */
	eachCell(func, transpon=false, r0=0, c0=0, r1, c1) {
		var rows = this.rowsCount(),
			cols = this.colsCount(),
			counter = 0;
		if (r1 == undefined || r1 >= rows) r1 = rows - 1;
		if (c1 == undefined || c1 >= cols) c1 = cols - 1;

		if (transpon) {
			for (var j=c0; j<=c1; j++)
				for (var i=r0; i<=r1; i++)
					if (func(this.cell(i, j), i, j, counter++) === false)
						return this;
			return this;
		}

		for (var i=r0; i<=r1; i++)
			for (var j=c0; j<=c1; j++)
				if (func(this.cell(i, j), i, j, counter++) === false)
					return this;

		return this;
	}

	/*
	 * content - линейный массив, либо двумерный
	 * transpon - по умолчанию false, приоритет строкам, true - приоритет колонкам
	 * r0, c0 - с какой ячейки начинать заполнение
	 * */
	setContent(content, transpon=false, r0=0, c0=0) {
		var r1, c1;

		if (!content[0].isArray) content = [content];

		if (transpon) {
			r1 = r0 + content[0].len;
			c1 = c0 + content.len - 1;
		} else {
			r1 = r0 + content.len - 1;
			c1 = c0 + content[0].len;
		}

		// Чтобы данные вошли в таблицу, возможно, нужно увеличить число строк
		if (r1 > this.rowsCount()) this.setRowCount(r1);

		this.eachCell((cell, r, c)=> {
			cell.text( transpon ? content[c][r] : content[r][c] );
		}, transpon, r0, c0, r1, c1);
	}

	resetContent(content, transpon=false, r0=0, c0=0) {
		this.clear();
		if (content.len)
			this.setContent(content, transpon, r0, c0);
	}

	insertRows(next, amt) {
		var cols = this.colsCount(),
			row = this.row(next),
			config = {key: 'r'};
		if (row) config.before = row;
		else config.parent = this;

		config.parentIndents = this.indents.get();
		config.cols = cols;
		var c = lx.TableRow.construct(amt, config);
		return c;
	}

	setRowCount(rows) {
		if (rows == this.rowsCount()) return this;

		if (rows < this.rowsCount()) {
			this.del('r', rows, this.rowsCount() - rows);
			return;
		}

		return this.insertRows(-1, rows - this.rowsCount());
	}

	addRows(amt=1) {
		return this.setRowCount( this.rowsCount() + amt );
	}

	delRows(num, amt) {
		this.del('r', num, amt);
		return this;
	}

	/*
	 * object.setRowsHeight('30px') - изменит высоту всех строк и запомнит как стандарт для таблицы
	 * */
	setRowHeight(height) {
		this.positioningStrategy.defaultSize = height;
		if (this.positioningStrategy.sizeBehavior == lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL)
			this.positioningStrategy.sizeBehavior = lx.StreamPositioningStrategy.SIZE_BEHAVIOR_SCROLLING;

		this.positioningStrategy.autoActualize = false;

		this.rows().each((a)=> a.height(height));

		this.positioningStrategy.autoActualize = true;
		this.positioningStrategy.actualize();

		return this;
	}

	// todo
	// setColCount(amt)
	// setColWidth(col, width)
	// merge(r0, c0, r1, c1)

	activeRow() {
		if (!this.interactiveInfo || !this.interactiveInfo.cellsForSelect) return null;
		if (this.interactiveInfo.row === undefined) return null;
		return this.interactiveInfo.row;
	}

	activeCell() {
		if (!this.interactiveInfo || !this.interactiveInfo.cellsForSelect) return null;
		if (this.interactiveInfo.cell === undefined) return null;
		return this.interactiveInfo.cell;
	}

	interactive(params={}) {
		if (!lx.TableManager) return;

		if (params === false) {
			lx.TableManager.unregisterTable(this);
			return this;
		}

		this.interactiveInfo = {
			cellsForSelect: params.cellsForSelect || lx.TableManager.cellsForSelect,
			autoRowAdding: params.autoRowAdding || lx.TableManager.autoRowAdding,
			cellEnterEnable: params.cellEnterEnable || lx.TableManager.cellEnterEnable
		};
		lx.TableManager.register(this);

		return this;
	}
}

lx.Table.DEFAULT_ROW_HEIGHT = '25px';
//=============================================================================================================================

//=============================================================================================================================
class TableRow extends Box #in lx {
	postBuildClient(config) {
		var colConfig = {
				direction: lx.HORIZONTAL,
				sizeBehavior:lx.StreamPositioningStrategy.SIZE_BEHAVIOR_PROPORTIONAL
			};
		if (config.parentIndents) {
			var indentData = config.parentIndents;
			if (indentData.stepX) colConfig.stepX = indentData.stepX;
			if (indentData.paddingLeft)   colConfig.paddingLeft  = indentData.paddingLeft;
			if (indentData.paddingRight)  colConfig.paddingRight = indentData.paddingRight;
		}

		this.stream(colConfig);
		lx.TableCell.construct(config.cols, {parent: this, key: 'c'});
	}

	table() {
		return this.parent;
	}

	cell(num) {
		if (!this.children.c) return null;
		if (this.children.c instanceof lx.TableCell) return this.children.c;
		if (!this.children.c[num]) return null;
		return this.children.c[num];
	}

	cells() {
		return new lx.Collection(this.children.c);
	}
}
//=============================================================================================================================

//=============================================================================================================================
class TableCell extends Box #in lx {
	table() {
		return this.parent.parent;
	}

	row() {
		return this.parent;
	}

	indexes() {
		return [
			this.parent.index || 0,
			this.index || 0
		];
	}

	rowIndex() {
		return this.parent.index;
	}
}
