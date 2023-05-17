#lx:namespace lx;
class Timer {
	constructor(config=0) {
		if (lx.isNumber(config) || lx.isArray(config)) config = {period: config};

		// может быть массивом - длительности будут чередоваться
		this.periodDuration = config.period;  // milliseconds

		// Функции, вызываемые при каждом обновлении фрейма
		// this.actions может быть массивом функций - они будут вызываться последовательно
		this.actions = config.action || config.actions || null;

		this.inAction = false;
		this.startTime = (new Date).getTime();
		this.countPeriods = lx.getFirstDefined(config.countPeriods, true);
		this.periodCounter = 0;
		this.periodIndex = 0;
		this.actionIndex = 0;
		
		this._action = function(){};
		this._iteration = function(){};
	}

	/**
	 * Метод, вызываемый при каждом обновлении фрейма
	 * */
	whileCycle(func) { this._action = func; }

	/**
	 * Метод, вызываемый при очередном завершении периода
	 * */
	onCycleEnds(func) { this._iteration = func; }

	/**
	 * Реализованы параллельно друг другу метод ._action() и поле .actions
	 * - метод может быть инициализирован с помощью метода [[whileCycle(func)]]
	 * - поле может быть инициализировано массивом функций, которые будут работать последовательно
	 * - метод и функции из поля-массива могут работать параллельно (метод в итерации сработает первым)
	 * */
	setAction(actions) {
		this.actions = actions;
	}

	/**
	 * Запуск, можно переопределить длительность периода
	 * */
	start(p) {
		if (this.inAction) return;
		if (p !== undefined) this.periodDuration = p;
		this.startTime = (new Date).getTime();
		lx.app.animation.addTimer(this);
		this.inAction = true;
	}

	/**
	 * Счетчик периодов при остановке не обнуляется! Если нужно обнулить - использовать метод resetCounter()
	 * */
	stop() {
		lx.app.animation.removeTimer(this);
		this.startTime = 0;
		this.inAction = false;
		this.periodIndex = 0;
		this.actionIndex = 0;
	}

	/**
	 * Сброс насчитанного времени внутри периода
	 * */
	resetTime() {
		this.startTime = (new Date).getTime();
	}

	/**
	 * Сброс счетчика периодов
	 * */
	resetCounter() {
		this.periodCounter = 0;
	}
	
	getCounter() {
		return this.periodCounter;
	}

	/**
	 * Относительное смещение во времени от начала периода до текущего момента - значение от 0 до 1
	 * */
	shift() {
		var time = (new Date).getTime(),
			delta = time - this.startTime,
			k = delta / __periodDuration(this);
		if (k > 1) k = 1;
		return k;
	}

	/**
	 * Проверка на то, что текущий период завершен
	 * */
	periodEnds() {
		// Если период не выставлен - постоянное срабатывание
		if (!this.periodDuration) return true;

		var time = (new Date).getTime();
		if (time - this.startTime >= __periodDuration(this)) {
			this.startTime = time;
			return true;
		}
		return false;
	}

	/**
	 * Используется системой - вызывается при каждом обновлении фрейма
	 * */
	go() {
		this._action.call(this);
		var action = __action(this);
		if (lx.isFunction(action)) action.call(this);
		if (!this.inAction) return;

		if (this.periodEnds()) {
			if (this.countPeriods) this.periodCounter++;
			this._iteration.call(this);

			if (this.periodDuration && lx.isArray(this.periodDuration)) {
				this.periodIndex++;
				if (this.periodIndex == this.periodDuration.length) this.periodIndex = 0;
			}

			if (this.actions && lx.isArray(this.actions)) {
				this.actionIndex++;
				if (this.actionIndex == this.actions.length) this.actionIndex = 0;
			}
		}
	}
}

function __periodDuration(self) {
	if (self.periodDuration && lx.isArray(self.periodDuration))
		return self.periodDuration[self.periodIndex];
	return self.periodDuration;
}

function __action(self) {
	if (self.actions && lx.isArray(self.actions))
		return self.actions[self.actionIndex];
	return self.actions;
}
