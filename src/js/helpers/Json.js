#lx:private;

lx.Json = {
	decode: function(str) {
		// Чтобы можно было парсить многострочники и табы
		var caret = String.fromCharCode(92) + String.fromCharCode(110),
			tab = String.fromCharCode(92) + String.fromCharCode(116);
		str = str.replace(/\n/g, caret);
		str = str.replace(/\t/g, tab);

		try {
			return JSON.parse(str);
		} catch (e) {
			var exp = str;
			while (true) {
				attempt = eliminateParseProblem(exp);
				if (attempt === false) throw e;
				if (attempt.success) return attempt.result;
				exp = attempt.string;
			}
		}
	},
	parse: function(str) {return this.decode(str);},

	/**
	 * У JS есть косяк - он {i:1} такое правильно упакует, а такое [i:1] нет -
	 * - содержимое ассоциативного массива будет проигнорировано
	 * */
	encode: function(data) {
		var result = {};
		function rec(from, to) {
			for (var i in from) {
				var item = from[i];
				if (item === null || item === undefined) {
					to[i] = null;
				} else if (item.isArray && !item.isAssoc) {
					to[i] = [];
					rec(item, to[i]);
				} else if (item.isObject || (item.isArray && item.isAssoc)) {
					to[i] = {};
					rec(item, to[i]);
				} else to[i] = from[i];
			}
		}
		rec(data, result);
		return JSON.stringify(result);
	},
	stringify: function(data) {return this.encode(data);}
};

function eliminateParseProblem(str) {
	try {
		var result = JSON.parse(str);
		return {success: true, result};
	} catch (e) {
		// Проблема неэкранированной двойной кавычки
		var i = e.message.match(/Unexpected (?:token|number) .+?(\d+)$/);
		if (i === null) {
			return false;
		} else i = +i[1];
		var pre = str.substring(0, i),
			post = str.substring(i);
		pre = pre.replace(/"([^"]*)$/, String.fromCharCode(92) + '"$1');
		return {
			success: false,
			string: pre + post
		};
	}
}
