#lx:namespace lx;
class ModuleEvent {
    constructor(target, params = {}) {
        this.target = target;
        for (let i in params)
            this[i] = params[i];
    }
}
