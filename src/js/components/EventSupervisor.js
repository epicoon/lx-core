#lx:private;

const listeners = {};

lx.EventSupervisor = {
	subscribe: function(eventName, callback) {
		if (!(eventName in listeners)) listeners[eventName] = [];
		if (callback.isFunction) listeners[eventName].push(callback);
		else if (callback.isArray)
			callback.each((item)=>{
				if (item.isFunction) listeners[eventName].push(item);
			});
	},

	trigger: function(eventName) {
		if (eventName in listeners)
			for (var i=0, l=listeners[eventName].len; i<l; i++)
				lx.callFunction(listeners[eventName][i]);
	}
};
