lx.EVENT_BEFORE_AJAX_REQUEST = 'beforeAjax';

let callbacks = {
    beforeAjax: []
};

lx.subscribe = function (eventName, callback) {
    if (!(eventName in callbacks)) return;
    callbacks[eventName].push(callback);
};

lx.deny = function (eventName, callback) {
    if (!(eventName in callbacks)) return;
    callbacks[eventName].lxRemove(callback);
};

lx.trigger = function (eventName, params = []) {
    if (!(eventName in callbacks)) return;
    callbacks[eventName].forEach(callback=>callback.apply(null, params));
};
