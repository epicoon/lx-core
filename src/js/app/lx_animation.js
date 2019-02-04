#private

/*
lx.addAction(f, ctx)
lx.resetActions()
lx.doActions()
lx.useTimers(bool)
lx.go()
*/

let timers = undefined,
	actions = [];

lx.addAction = function(f, ctx) {
	actions.push({ func: f, context: ctx });
};

lx.resetActions = function() {
	actions = [];
};

lx.addTimer = function(timer) {
	if (!timers) return;
	timers.add(timer);
};

lx.removeTimer = function(timer) {
	if (!timers) return;
	timers.remove(timer);
};

lx.useTimers = function(bool) {
	if (!bool) {
		timers = undefined;
		return;
	}

	timers = {
		data: [],
		add: function(one) {
			this.data.pushUnique(one);
		},
		remove: function(one) {
			this.data.remove(one);
		},
		go: function() {
			for (var i=0; i<this.data.length; i++) {
				if (this.data[i].go != undefined)
					this.data[i].go();
			}
		}
	};
};

lx.doActions = function() {
	if (timers != undefined) timers.go();

	for (var i=0, l=actions.length; i<l; i++) {
		var f = actions[i].func,
			ctx = actions[i].context;
		f.call(ctx);
	}
};

lx.go = Function("a", "(function animate() {requestAnimationFrame(animate);a[0].call(null);})();");
