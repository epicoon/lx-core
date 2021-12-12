global.lx = {};

Object.defineProperty(lx, "globalContext", {
    get: function () {
        return global;
    }
});

#lx:require commonCore;
#lx:require server/app/scripts/lx_core;
