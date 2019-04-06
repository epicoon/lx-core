#lx:private;

const listeners = {};

lx.EventSupervisor = {
	subscribe: function(eventName, callback) {
		if (!(eventName in listeners)) listeners[eventName] = [];
		listeners[eventName].push(callback);
	},

	trigger: function(eventName) {
		if (eventName in listeners)
			for (var i=0, l=listeners[eventName].len; i<l; i++)
				lx.callFunction(listeners[eventName][i]);
	}
};
