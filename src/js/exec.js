const nodeModules = {
	fs: require('fs'),
	path: require('path')
};

let err = 0;
let res = 0;
let __out__ = {
	logList: [],
	dumpList: []
};

let filePath = process.argv[2];
let code = nodeModules.fs.readFileSync(filePath, 'utf8');

try {
	var f = new Function('__nodeModules__, __out__', code);
	res = f(nodeModules, __out__) || null;
} catch (e) {
	returnError(e, 1);
	return;
}

var result = null;
try {
	result = JSON.stringify({
		error: err,
		result: res,
		log: __out__.logList,
		dump: __out__.dumpList
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
		log: __out__.logList,
		dump: __out__.dumpList
	}));
}
