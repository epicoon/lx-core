class ModuleEvent #lx:namespace lx {
    constructor(target, params = {}) {
        this.target = target;
        for (let i in params)
            this[i] = params[i];
    }
}
