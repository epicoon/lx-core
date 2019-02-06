#private;

lx.Json = {
	// Чтобы можно было парсить многострочники и табы
	parse: function(str) {
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
	}
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
