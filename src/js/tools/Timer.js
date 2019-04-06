class Timer #lx:namespace lx {
	constructor(p=0) {
		if (p.isNumber || p.isArray) p = {period: p};

		this.inAction = false;
		this.startTime = (new Date).getTime();

		this.counterOn = true;
		this.periodCounter = 0;
		// this.periodDuration может быть массивом - длительности будут чередоваться
		this.periodDuration = p.period;  // milliseconds
		this.periodIndex = 0;

		// Функции, вызываемые при каждом обновлении фрейма
		// this.actions может быть массивом функций - они будут вызываться последовательно
		this.actions = p.action || p.actions || null;
		this.actionIndex = 0;
	}

	/**
	 * Метод, вызываемый при каждом обновлении фрейма
	 * */
	action() {}

	/**
	 * Метод, вызываемый при очередном завершении периода
	 * */
	iteration() {}

	/**
	 * Реализованы параллельно друг другу метод .action() и поле .actions
	 * - метод может быть перепределен у потомка
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
		lx.addTimer(this);
		this.inAction = true;
	}

	/**
	 * Счетчик периодов при остановке не обнуляется! Если нужно обнулить - использовать метод resetCounter()
	 * */
	stop() {
		lx.removeTimer(this);
		this.startTime = 0;
		this.inAction = false;
	}

	/**
	 * Сброс счетчика периодов
	 * */
	resetCounter() {
		this.periodCounter = 0;
	}

	/**
	 * Относительное смещение во времени от начала периода до текущего момента - значение от 0 до 1
	 * */
	shift() {
		var time = (new Date).getTime(),
			delta = time - this.startTime,
			k = delta / this.__periodDuration();
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
		if (time - this.startTime >= this.__periodDuration()) {
			this.startTime = time;
			return true;
		}
		return false;
	}

	/**
	 * Используется системой - вызывается при каждом обновлении фрейма
	 * */
	go() {
		this.action();
		var action = this.__action();
		if (action && action.isFunction) action.call(this);

		if (this.periodEnds()) {
			if (this.counterOn) this.periodCounter++;
			this.iteration();

			if (this.periodDuration && this.periodDuration.isArray) {
				this.periodIndex++;
				if (this.periodIndex == this.periodDuration.length) this.periodIndex = 0;
			}

			if (this.actions && this.actions.isArray) {
				this.actionIndex++;
				if (this.actionIndex == this.actions.length) this.actionIndex = 0;
			}
		}
	}

	__periodDuration() {
		if (this.periodDuration && this.periodDuration.isArray)
			return this.periodDuration[this.periodIndex];
		return this.periodDuration;
	}

	__action() {
		if (this.actions && this.actions.isArray)
			return this.actions[this.actionIndex];
		return this.actions;
	}
}
