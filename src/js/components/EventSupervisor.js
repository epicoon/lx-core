#lx:private;

const listeners = {};

lx.EventSupervisor = {
	subscribe: function(eventName, callback) {
		if (!(eventName in listeners)) listeners[eventName] = [];
		listeners[eventName].push(callback);
	},

	trigger: function(eventName, args = []) {
		if (eventName in listeners) {
			if (!args.isArray) args = [args];
			for (var i=0, l=listeners[eventName].len; i<l; i++)
				lx._f.callFunction(listeners[eventName][i], args);
		}
	}
};
