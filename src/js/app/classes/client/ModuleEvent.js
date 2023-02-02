#lx:namespace lx;
class ModuleEvent {
    constructor(target, params = {}) {
        this.target = target;
        this._stopped = false;
        for (let i in params)
            this[i] = params[i];
    }

    preventDefault() {
        this._stopped = true;
    }

    isPrevented() {
        return this._stopped;
    }
}
