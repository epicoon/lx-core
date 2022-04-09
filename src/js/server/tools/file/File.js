//TODO не все методы, что есть на PHP, реализованы тут. Надо доделать
#lx:namespace lx;
class File extends lx.BaseFile {
    get() {
        return lx.node.fs.readFileSync(this.path, 'utf8');
    }
}
