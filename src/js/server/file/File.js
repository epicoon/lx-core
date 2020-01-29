//TODO не все методы, что есть на PHP, реализованы тут. Надо доделать
class File extends lx.BaseFile #lx:namespace lx {
    get() {
        return lx.node.fs.readFileSync(this.path, 'utf8');
    }
}
