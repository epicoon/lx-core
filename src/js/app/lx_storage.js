#lx:private;

let cache = {},
	sessionCache = {};

lx.Storage = {
	get: function(key) {
		if (!(key in cache)) {
			var val = localStorage.getItem(key);
			if (val !== undefined && val !== null) {			
				cache[key] = lx.Json.decode(val);
			}
		}

		return cache[key];
	},

	set: function(key, value) {
		cache[key] = value;

		try {
			localStorage.setItem(key, lx.Json.encode(value));
		} catch (e) {
			if (e == QUOTA_EXCEEDED_ERR) {
				console.log('localStorage is full');
			}
		}
	},

	remove: function(key) {
		localStorage.removeItem(key);
	},

	clear: function() {
		localStorage.clear();
	},

	sessionGet: function(key) {
		if (!(key in sessionCache)) {
			var val = sessionStorage.getItem(key);
			if (val !== undefined && val !== null) {			
				sessionCache[key] = lx.Json.decode(val);
			}
		}

		return sessionCache[key];
	},

	sessionSet: function(key, value) {
		sessionCache[key] = value;

		try {
			sessionStorage.setItem(key, lx.Json.encode(value));
		} catch (e) {
			if (e == QUOTA_EXCEEDED_ERR) {
				console.log('sessionStorage is full');
			}
		}
	},

	sessionRemove: function(key) {
		sessionStorage.removeItem(key);
	},

	sessionClear: function() {
		sessionStorage.clear();
	}
};

