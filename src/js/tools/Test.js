class Test #in lx {
	constructor() {
		this.mode = self::MODE_CONSOLE;
		this.onFail = self::ON_FAIL_CONTINUE;

		// Надо включать перед каждым нужным тестом - по умолчанию не работает
		this.logOn = false;
	}

	/* * * * *  Используемые методы  * * * * */

	check(statement, message='') {
		// if (!statement) throw {
		// 	checkFail: true,
		// 	message
		// };

		if (!statement) {
			this.testIsOk = false;
			if (self::_afterCurrentFail) self::_afterCurrentFail();
			this.afterTestFail(self::_currentTest, message);
			this._endTest(self::_currentTest, false, message);
		}
		return statement;
	}

	log(text) {
		if (!this.logOn) return;

		if (this.mode == self::MODE_CONSOLE)
			console.log(text);
		else if (this.mode == self::MODE_SCREEN) {
			this.logger.decorate({style:{color:'white'}});
			this.logger.log(text);
		}
	}

	/**
	 * Метод для вызова только внутри метода-места
	 * */
	afterThisTest(callback) {
		if (self::_currentTest === null) return;
		self::_afterCurrent = callback;
	}

	/**
	 * Метод для вызова только внутри метода-места
	 * */
	afterThisTestSuccess(callback) {
		if (self::_currentTest === null) return;
		self::_afterCurrentSuccess = callback;
	}

	/**
	 * Метод для вызова только внутри метода-места
	 * */
	afterThisTestFail(callback) {
		if (self::_currentTest === null) return;
		self::_afterCurrentFail = callback;
	}

	/* * * * *  Переопределяемые методы  * * * * */

	forRun() {
		return true;
	}

	beforeTest(testName) {}
	afterTest(testName) {}
	afterTestSuccess(testName) {}
	afterTestFail(testName, error) {}

	/* * * * *  Приватные методы  * * * * */

	_run() {
		var forRun = this.forRun();
		if (forRun.isString) {
			if (!forRun.match(/^test/) || !this.hasMethod(forRun)) return;
			this._runTest(forRun);
		} else if (forRun.isArray) {
			for (var i=0, l=forRun.len; i<l; i++) {
				var funcName = forRun[i];
				if (!funcName.match(/^test/) || !this.hasMethod(funcName)) continue;

				var result = this._runTest(funcName);
				if (this.onFail == self::ON_FAIL_STOP && !result) return;
			}
		} else if (forRun === true) {
			var funcs = this.getAllProperties();
			for (var i=0, l=funcs.len; i<l; i++) {
				var funcName = funcs[i];
				if (!funcName.match(/^test/)) continue;

				var result = this._runTest(funcName);
				if (this.onFail == self::ON_FAIL_STOP && !result) return;
			}
		}
	}

	_runTest(testName) {
		this.logOn = false;

		self::_currentTest = testName;

		this.beforeTest(testName);

		this.testIsOk = true;
		this[testName]();

		// try {
		// 	this[testName]();
		// } catch (error) {
		// 	console.dir(error);
		// 	// if (!error.checkFail) throw error;
		// 	console.error(error.stack);
		// 	var msg = error.message;
		// 	if (self::_afterCurrentFail) self::_afterCurrentFail();
		// 	this.afterTestFail(testName, msg);
		// 	return this._endTest(testName, false, msg);
		// }

		if (this.testIsOk) {
			if (self::_afterCurrentSuccess) self::_afterCurrentSuccess();
			this.afterTestSuccess(testName);
			return this._endTest(testName, true);
		} else return false;
	}

	_endTest(testName, success, msg='') {
		if (self::_afterCurrent) self::_afterCurrent();
		this.afterTest(testName);

		this._print(success, msg);
		self::_currentTest = null;
		self::_afterCurrent = null;
		self::_afterCurrentSuccess = null;
		self::_afterCurrentFail = null;
		return success;
	}

	_print(success, msg='') {
		if (this.mode == self::MODE_CONSOLE)
			this._toConsole(success, msg);
		else if (this.mode == self::MODE_SCREEN)
			this._toScreen(success, msg);
	}

	_toConsole(success, msg) {
		var method = success ? 'log' : 'error';
		console[method](this._getText(success, msg));
	}

	_toScreen(success, msg) {
		if (success) this.logger.decorate({style:{color:'lightgreen'}});
		else this.logger.decorate({style:{color:'red'}});
		this.logger.log(this._getText(success, msg));
	}

	_getText(success, msg) {
		var text = success
			? self::name + '::' + self::_currentTest + ': OK'
			: self::name + '::' + self::_currentTest + ': FAIL';
		if (msg != '') text += '; ' + msg;
		return text;
	}

	_init() {
		if (this.mode == self::MODE_SCREEN) {
			let log = new lx.LogBox();
			log.fill('black');
			log.style('color', 'white');

			this.logger = log;
		}
	}

	static __afterDefinition() {
		let test = new this();
		test._init();
		test._run();
	}
}

lx.Test._currentTest = null;
lx.Test._afterCurrent = null;
lx.Test._afterCurrentSuccess = null;
lx.Test._afterCurrentFail = null;

lx.Test.MODE_CONSOLE = 1;
lx.Test.MODE_SCREEN = 2;

lx.Test.ON_FAIL_STOP = 3;
lx.Test.ON_FAIL_CONTINUE = 4;
