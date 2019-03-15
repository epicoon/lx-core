lx.timetest = function(msg, forse=false) {
	if (!forse && !lx.timetest.status) return;
	msg = msg ? (msg+'>>> ') : '';
	var time = new Date().getTime(),
		last = lx.timetest.last;
	lx.timetest.last = time;
	if (!last) return;
	console.log( msg + 'duration: ' + (time - last) + ' ms' );
};

lx.timetest.last = 0;
lx.timetest.status = true;

lx.timetest.start = function() {
	lx.timetest.last = new Date().getTime();
};

lx.timetest.condition = function(bool = true) {
	lx.timetest.status = bool;
	if (bool) lx.timetest.last = new Date().getTime();
};
