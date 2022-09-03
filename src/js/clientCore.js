(()=>{
    const lx = {};
    Object.defineProperty(lx, 'globalContext', {
        get: function () {
            return window;
        }
    });
    lx.globalContext.lx = lx;

    #lx:require app/Application;
    #lx:require -R classes/common/;
    #lx:require -R classes/client/;
    lx.app = new lx.Application();
})();
