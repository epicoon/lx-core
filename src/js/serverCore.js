(()=>{
    const lx = {};
    Object.defineProperty(lx, 'globalContext', {
        get: function () {
            return global;
        }
    });
    lx.globalContext.lx = lx;

    #lx:require app/Application;
    #lx:require -R classes/common/;
    #lx:require -R classes/server/;
    lx.app = new lx.Application();
})();
