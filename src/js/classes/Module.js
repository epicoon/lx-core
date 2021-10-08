class Module #lx:namespace lx {
    /**
     * @param key - ключ вызываемого метода
     * @param params - параметры, с которыми нужно вызвать метод
     */
    static ajax(key, params = []) {
        return new lx.ModuleRequest(this.lxFullName(), key, params);
    }
}
