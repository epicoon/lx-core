const fs = require("fs");

let err = 0;
let res = 0;
let __out__ = {
	logList: []
};

let filePath = process.argv[2];
let code = fs.readFileSync(filePath, "utf8");

try {
	var f = new Function('__out__', code);
	res = f(__out__) || null;
} catch (e) {
	returnError(e, 1);
	return;
}

var result = null;
try {
	result = JSON.stringify({
		error: err,
		result: res,
		log: __out__.logList
	});
} catch (e) {
	returnError(e, 2);
	return;
}

console.log(result);

function returnError(e, code) {
	console.log(JSON.stringify({
		error: code,
		result: {
			name: e.name,
			message: e.message,
			stack: e.stack
		},
		log: __out__.logList
	}));
}
