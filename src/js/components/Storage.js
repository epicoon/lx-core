let useCache = false,
    cache = {},
    sessionCache = {};

lx.Storage = {
    useCache: function(bool = true) {
        useCache = bool;
    },

    get: function(key) {
        if (!useCache) return __get(key);
        if (!(key in cache)) cache[key] = __get(key);
        return cache[key];
    },

    set: function(key, value) {
        if (useCache) cache[key] = value;
        __set(key, value);
    },

    remove: function(key) {
        localStorage.removeItem(key);
    },

    clear: function() {
        localStorage.clear();
    },

    sessionGet: function(key) {
        if (!useCache) return  __sessionGet(key);
        if (!(key in sessionCache)) sessionCache[key] = __sessionGet(key);
        return sessionCache[key];
    },

    sessionSet: function(key, value) {
        if (useCache) sessionCache[key] = value;
        __sessionSet(key, value);
    },

    sessionRemove: function(key) {
        sessionStorage.removeItem(key);
    },

    sessionClear: function() {
        sessionStorage.clear();
    }
};

function __get(key) {
    var val = localStorage.getItem(key);
    if (val !== undefined && val !== null) val = lx.Json.decode(val);
    return val;
}

function __set(key, value) {
    try {
        localStorage.setItem(key, lx.Json.encode(value));
    } catch (e) {
        console.error('Local storage error');
        console.log(e);
    }
}

function __sessionGet(key) {
    var val = sessionStorage.getItem(key);
    if (val !== undefined && val !== null) val = lx.Json.decode(val);
    return val;
}

function __sessionSet(key, value) {
    try {
        sessionStorage.setItem(key, lx.Json.encode(value));
    } catch (e) {
        if (e == QUOTA_EXCEEDED_ERR) {
            console.log('sessionStorage is full');
        }
    }
}
