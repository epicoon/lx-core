lx.Date = function() {
	/*todo нормально доработать дату
		- по ES6 переписать
		- в тулзы засунуть
		- учитывать часы, минуты, секунды
		- парсить перулярками, чтобы разделитель не был привязан к "-"
		- вообще сделать симметрично тому, как с датами работает PHP
	*/
	var object, a = arguments;
	switch (a.length) {
		case 0 : object = new Date(); break;
		case 1 : object = new Date(a[0]); break;
		case 2 : object = new Date(a[0], a[1]); break;
		case 3 : object = new Date(a[0], a[1], a[2]); break;
		case 4 : object = new Date(a[0], a[1], a[2], a[3]); break;
		case 5 : object = new Date(a[0], a[1], a[2], a[3], a[4]); break;
		case 6 : object = new Date(a[0], a[1], a[2], a[3], a[4], a[5]); break;
		default : object = new Date(a[0], a[1], a[2], a[3], a[4], a[5], a[6]);
	};

	object.getMonth_mm = function() {
		var newMonth = this.getMonth()+1;
		return ((newMonth<10)?'0'+newMonth:newMonth);
	};

	object.getYear = function() {
		var year = this.getFullYear();
		return year % 100;
	};

	/**
	 * Формат по умолчанию 'dd-mm-yyyy'
	 * */
	object.format = function(format='dd-mm-yyyy') {
		var sequence = format.split('-');
		if ( sequence.length != 3 ) return '';
		var map = [], yFormat;
		for (var i=0; i<3; i++)
			if (sequence[i].charAt(0) == 'y') yFormat = sequence[i];
		map['d'] = this.getDate();
		map['m'] = this.getMonth_mm();
		map['y'] = (yFormat == 'yy') ? this.getYear() : this.getFullYear();
		var result = [];
		for (var i=0; i<3; i++)
			result.push( map[ sequence[i].charAt(0) ] );
		return result.join('-');
	};

	object.getDBformat = function() {
		return this.getFormat('yyyy-mm-dd');
	};

	object.setFormat = function(str, format) {
		format = format || 'dd-mm-yyyy';
		var sequence = format.split('-'),
			arr = str.split('-');
		if ( sequence.length != 3 ) return this;
		if ( arr.length != 3 ) return this;
		var map = [];
		for (var i=0; i<3; i++)
			map[ sequence[i].charAt(0) ] = arr[i];
		this.setFullYear( map['y'], map['m']-1, map['d'] );
		return this;
	};

	object.shiftDay = function(amt) {
		var d = lx.Date();
		d.setDate( this.getDate() + amt );
		return d;
	};

	object.shiftMonth = function(amt) {
		var d = lx.Date( this.getFullYear(), this.getMonth(), this.getDay() );
		d.setMonth( this.getMonth() + amt );
		return d;
	};

	object.getMaxDate = function(y, m) {
		y = y || this.getFullYear();
		m = m || this.getMonth();
		if (m == 1) return y%4 || (!(y%100) && y%400 ) ? 28 : 29;
		return m===3 || m===5 || m===8 || m===10 ? 30 : 31;
	};

	object.monthNameRu = function(num) {
		num = num || this.getMonth();
		var names = lx.Date.monthNamesRu();
		return names[ num ];
	};

	return object;
};

lx.Date.monthNamesRu = function() {
	return	['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
};
