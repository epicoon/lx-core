lx.Queues = {
	list: {},

	add: function(qName, task) {
		if (!(qName in this.list)) {
			this.list[qName] = new Queue(qName);
		}

		this.list[qName].add(task);
	}
};

class Task #lx:namespace lx {
	#lx:const
		STATUS_NEW = 1,
		STATUS_PENDING = 2,
		STATUS_COMPLETED = 3;

	constructor(queue, callback = null) {
		this.setCallback(callback);
		this.status = self::STATUS_NEW;
		if (queue) this.setQueue(queue);
	}

	setQueue(queue) {
		if (lx.isString(queue)) lx.Queues.add(queue, this);
		else this.queue = queue;
		return this;
	}

	setCallback(callback) {
		this.callback = callback;
		return this;
	}

	run() {
		this.setPending();
		if (this.callback) this.callback();
	}

	isStatus(status) {
		return this.status == status;
	}

	isNew() {
		return this.isStatus(self::STATUS_NEW);
	}

	isPending() {
		return this.isStatus(self::STATUS_PENDING);
	}

	isCompleted() {
		return this.isStatus(self::STATUS_COMPLETED);
	}

	setStatus(status) {
		this.status = status;
	}

	setPending() {
		this.setStatus(self::STATUS_PENDING);
	}

	setCompleted() {
		this.setStatus(self::STATUS_COMPLETED);
	}
}

var __queueCounter = 0;
function __getQueueKey() {
	return 'q' + __queueCounter++;
}

class Queue {
	constructor(name) {
		this.name = name;
		this.list = {};
		this.keys = [];
		this.counter = 0;
		this.active = false;
	}

	add(task) {
		var key = __getQueueKey();
		task.__lxQKey = key;
		this.keys.push(key);
		this.list[key] = task;
		task.setQueue(this);
		if (!this.active) {
			this.active = true;
			lx.addTimer(this);
		}
	}

	go() {
		if (this.keys.len) {
			var task = this.getTask(0);
			if (task.isPending()) return;
			if (task.isNew()) task.run();
			else if (task.isCompleted()) this.shiftTask();
		}

		if (!this.keys.len) {
			lx.removeTimer(this);
			this.active = false;
		}
	}

	getTask(num) {
		return this.list[this.keys[num]];
	}

	shiftTask() {
		delete this.list[this.keys[0]];
		this.keys.shift();
	}
}
