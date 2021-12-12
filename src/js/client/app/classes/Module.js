class Module #lx:namespace lx {
    /**
     * @param {String} key - ключ вызываемого метода
     * @param {Array} params - параметры, с которыми нужно вызвать метод
     */
    static ajax(key, params = []) {
        return new lx.ModuleRequest(this.lxFullName(), key, params);
    }
}
