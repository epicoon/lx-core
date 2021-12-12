/*
lx.move(event)
lx.useElementMoving(bool = true)
*/

let moved = false,
	movedDelta = { x: 0, y: 0 },
	movedElement = null;

lx.move = function(event) {
	event = event || window.event;
	lx.Event.preventDefault(event);

	moved = true;
	movedElement = this;

	var X = event.clientX || event.changedTouches[0].clientX,
		Y = event.clientY || event.changedTouches[0].clientY;

	var el = (this.moveParams.parentMove || this.moveParams.parentResize) ? this.parent : this;
	el.emerge();

	delete el.geom.bpg;
	delete el.geom.bpv;

	if (this.moveParams.parentResize) {
		var p = this.parent;
		movedDelta.x = X - p.left('px') - p.width('px');
		movedDelta.y = Y - p.top('px') - p.height('px');
		this.trigger('moveBegin', event);
		this.parent.trigger('resizeBegin', event);
		return;
	}
	
	movedDelta.x = X - el.left('px');
	movedDelta.y = Y - el.top('px');

	if (this.moveParams.parentMove) this.trigger('moveBegin', event);
	el.trigger('moveBegin', event);
};

lx.useElementMoving = function(bool = true) {
	var method = bool ? 'on' : 'off';
	this[method]('mousemove', watchForMove);
	this[method]('mouseup', resetMovedElement);
	this[method]('touchmove', watchForMove);
	this[method]('touchend', resetMovedElement);
};

function resetMovedElement(event) {
	if (movedElement == null) return;

	var el = movedElement;
	moved = false;
	movedElement = null;

	el.trigger('moveEnd', event);
	if (el.moveParams.parentResize && el.parent) el.parent.trigger('resizeEnd', event);
}

function watchForMove(event) {
	if (!moved) return;
	if (movedElement == null) return;

	if (movedElement.moveParams.locked) {
		moved = false;
		movedElement = null;
		return;
	}

	event = event || window.event;

	var el = movedElement,
		info = el.moveParams;
	
	var X, Y;
	if (event.clientX) X = event.clientX;
	else if (event.changedTouches) X = event.changedTouches[0].clientX;
	if (event.clientY) Y = event.clientY;
	else if (event.changedTouches) Y = event.changedTouches[0].clientY;

	var newPos = {
		x: X - movedDelta.x,
		y: Y - movedDelta.y
	};

	if (info.parentResize) {
		var p = el.parent, pp = p.parent;
		if (info.xMove) {
			if (info.moveStep > 1) newPos.x = Math.floor( newPos.x / info.moveStep ) * info.moveStep;
			if (info.xLimit && newPos.x > pp.width('px')) newPos.x = pp.width('px');
			p.width( newPos.x - p.left('px') + 'px' );
		}
		if (info.yMove) {
			if (info.moveStep > 1) newPos.y = Math.floor( newPos.y / info.moveStep ) * info.moveStep;
			if (info.yLimit && newPos.y > pp.height('px')) newPos.y = pp.height('px');
			p.height( newPos.y - p.top('px') + 'px' );
		}

		el.trigger('move', event);
		p.trigger('resize', event);

		return;
	}

	var movedEl = (info.parentMove) ? el.parent : el,
		parent = movedEl.parent;

	if (info.xMove) {
		if (info.moveStep > 1) newPos.x = Math.floor( newPos.x / info.moveStep ) * info.moveStep;
		if (info.xLimit) {
			if (newPos.x + movedEl.width('px') > parent.width('px'))
				newPos.x = parent.width('px') - movedEl.width('px');
			if (newPos.x < 0) newPos.x = 0;
		}

		movedEl.left( newPos.x + 'px' );
	}

	if (info.yMove) {
		if (info.moveStep > 1) newPos.y = Math.floor( newPos.y / info.moveStep ) * info.moveStep;
		if (info.yLimit) {
			if (newPos.y + movedEl.height('px') > parent.height('px'))
				newPos.y = parent.height('px') - movedEl.height('px');
			if (newPos.y < 0) newPos.y = 0;
		}
		movedEl.top( newPos.y + 'px' );
	}

	if (info.parentMove) el.trigger('move', event);
	movedEl.trigger('move', event);
}
