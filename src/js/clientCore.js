(()=>{
    let __onReady = [];
    const lx = {
        onReady: callback => {(lx && lx.app && lx.app.isReady()) ? callback() : __onReady.push(callback);},
        dropReady: ()=> __onReady=[]
    };
    Object.defineProperty(lx, 'globalContext', {
        get: function () {
            return window;
        }
    });
    lx.globalContext.lx = lx;

    #lx:require app/Application;
    #lx:require -R classes/common/;
    #lx:require -R classes/client/;
    lx.app = new lx.Application(__onReady);
})();
