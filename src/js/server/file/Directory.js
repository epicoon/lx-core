//TODO не все методы, что есть на PHP, реализованы тут. Надо доделать
class Directory extends lx.BaseFile #lx:namespace lx {
    #lx:const
        FIND_NAME = 1,
        FIND_OBJECT = 2;

    scan() {
        return lx.node.fs.readdirSync(this.path);
    }

    find(filename, flag = lx.Directory.FIND_OBJECT) {
        var arr = filename.split('/');
        var f;
        if (arr.len == 1) {
            f = __staticFind(this.path, filename);
        } else {
            var medName = arr.shift();
            var fn = arr.join('/');
            f = __staticFindExt(this.path, medName, fn);
        }

        if (!f) return false;
        if (flag == self::FIND_NAME) return f;
        return lx.BaseFile.construct(f);
    }
}

function __staticFind(dirName, fileName) {
    if ( ! lx.node.fs.existsSync(dirName)) return false;
    var files = lx.node.fs.readdirSync(dirName);
    if (dirName[dirName.length - 1] != '/') dirName += '/';
    for (let f of files) {
        var path = dirName + f;
        if (f == fileName) return path;
        var stat = lx.node.fs.statSync(path);
        if (stat.isDirectory()) {
            var res = __staticFind(path, fileName);
            if (res) return res;
        }
    }
    return false;
}

function __staticFindExt(dirName, medDirName, fileName) {
    if ( ! lx.node.fs.existsSync(dirName)) return false;
    var files = lx.node.fs.readdirSync(dirName);
    if (dirName[dirName.length - 1] != '/') dirName += '/';
    if (medDirName[medDirName.length - 1] != '/') medDirName += '/';
    for (let f of files) {
        var path = dirName + f;
        if (f == medDirName) {
            var fullPath = path + fileName;
            if (lx.node.fs.existsSync(fullPath)) {
                return fullPath;
            }
        }
        var stat = lx.node.fs.statSync(path);
        if (stat.isDirectory()) {
            var res = __staticFindExt(path, medDirName, fileName);
            if (res) return res;
        }
    }
    return false;
}
