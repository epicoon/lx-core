#lx:private;

let __isGuest = true;
let __publicFieldNames = [];
let __publicFields = {};

lx.User = {
    isGuest: function() {
        return __isGuest;
    },

    getFieldNames: function() {
        return __publicFieldNames.lxClone();
    },

    getFields: function() {
        return __publicFields.lxClone();
    },

    set: function(data) {
        for (let key in data) {
            __publicFieldNames.push(key);
            __publicFields[key] = data[key];
            
            Object.defineProperty(this, key, {
                get: function() {
                    return __publicFields[key];
                }
            });
        }
    },

    setGuestFlag: function(flag) {
        __isGuest = flag;
    }
};
