window.lx = {};

Object.defineProperty(lx, "globalContext", {
    get: function () {
        return window;
    }
});

#lx:require commonCore;
#lx:require -R common/tools/;
#lx:require -R client/;

lx.plugins = new lx.Dict();
