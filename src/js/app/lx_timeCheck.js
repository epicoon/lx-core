lx.timeCheck = function(msg, forse=false) {
    if (!forse && !lx.timeCheck.status) return;
    msg = msg ? (msg+'>>> ') : '';
    var time = new Date().getTime(),
        last = lx.timeCheck.last;
    lx.timeCheck.last = time;
    if (!last) return;
    console.log( msg + 'duration: ' + (time - last) + ' ms' );
};

lx.timeCheck.last = 0;
lx.timeCheck.status = true;

lx.timeCheck.start = function() {
    lx.timeCheck.last = new Date().getTime();
};

lx.timeCheck.condition = function(bool = true) {
    lx.timeCheck.status = bool;
    if (bool) lx.timeCheck.last = new Date().getTime();
};
