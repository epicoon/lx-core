(()=>{
    let __onReady = [];

    const lx = {
        onReady: callback => {(lx && lx.app && lx.app.isReady()) ? callback() : __onReady.push(callback);},
        dropReady: ()=> __onReady=[]
    };

    //TODO не работает как мне надо - выбрасывается исключение не только при обращении к полю, но и когда в невызванном коде такое обращение есть
    // const lx = new Proxy({
    //     onReady: callback => {(lx && lx.app && lx.app.isReady()) ? callback() : __onReady.push(callback);},
    //     dropReady: ()=> __onReady=[]
    // }, {
    //     get: function(target, prop, receiver) {
    //         if (target.app && target.app.isReady() && !(prop in target)) {
    //             throw new Error(`lx namespace does not contain '${prop}'.`);
    //         }
    //         return target[prop];
    //     }
    // });

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
