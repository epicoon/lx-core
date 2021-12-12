/**
 * Универсальный синглтон, позволяющий получать один и тот же экземпляр прямо при вызове конструктора через new!
 * У потомков в качестве метода конструирования объекта надо использовать не constructor(), а init()
 * */
class Singleton #lx:namespace lx {
	/**
	 * Синглтон прямо через конструктор. Js - ты космос.
	 * Коструктор доопределять у потомков не нужно - вместо него все полагающееся конструктору надо писать в методе init()
	 * */
	constructor(...args) {
		if (self::getInstance === undefined || self::getInstance().constructor !== this.constructor) {
			initInstance(this.constructor);
			self::setInstance(this);
			this.init.apply(this, args);
		} else return self::getInstance();
	}

	/**
	 * Метод для переопределения потомками, выполняющий роль конструктора
	 * */
	init() {}
}

/**
 * Функция, скрытая от клиентского кода - здесь вся магия зарыта
 * */
function initInstance(ctx) {
	// Функция создает замыкание, чтобы хранить внешне недоступный экземпляр инстанса
	(function() {
		var instance = null;
		return function() {
			// Метод доступа к экземпляру инстанса будет у класса-потомка
			this.getInstance = function() { return instance; };
			// Метод инициализации экземпляра инстанса одноразовый - только для первоначальной инициализации
			this.setInstance = function(val) { instance = val; delete this.setInstance; };
		};		
	})().call(ctx);
}
