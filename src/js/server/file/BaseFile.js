class BaseFile #lx:namespace lx {
    #lx:const
        WRONG = 0,
        DIR = 1,
        FILE = 2;

    constructor(path, stat = null) {
        /* this.path
         * this.name
         * this.parentDirPath
         */
        this.setPath(path);
        this.__stat = stat || lx.node.fs.statSync(path);
    }

    setPath(path) {
        this.__path = path;
        var arr = path.split('/');

        this.__name = arr.pop();
        // если path заканчивался на '/'
        if (this.name == '') this.name = arr.pop();

        this.__parentDirPath = arr.join('/');

        return this;
    }

    get path() { return this.__path; }
    get name() { return this.__name; }
    get parentDirPath() { return this.__parentDirPath; }

    static getFileOrDir(path) {
        var stat = lx.node.fs.statSync(path);
        if (stat.isDirectory()) {
            return new lx.Directory(path, stat);
        }

        if (stat.isFile()) {
            return new lx.File(path, stat);
        }

        return null;
    }

    getParentDir() {
        return (new lx.Directory(this.__parentDirPath));
    }

    exists() {
        try {
            if (lx.node.fs.existsSync(this.__path)) return true;
        } catch(err) {
            return false;
        }
    }

    belongs(parent) {
        var path;
        if (parent.isString) {
            path = parent;
        } else {
            if (parent instanceof lx.Directory) {
                path = parent.path;
            } else {
                return false;
            }
        }

        path = lx.addcslashes(path, '/');
        var reg = new RegExp('/^' + path + '/');
        return reg.test(this.path);
    }

    getRelativePath(parent) {
        if ( ! this.belongs(parent)) {
            return false;
        }

        var path = false;
        if (parent.isString) {
            path = parent;
        } else {
            if (parent instanceof lx.Directory) {
                path = parent.path;
            } else {
                return false;
            }
        }

        var selfPath = this.path;
        path = lx.addcslashes(path, '/');
        var reg = new RegExp('/^' + path + '\/' + '/');
        return selfPath.replace(reg, '');
    }

    changedAt(units = '') {
        if ( ! this.exists()) {
            return Infinity;
        }

        if (units == '') return this.__stat.ctime;
        if (units == 'ms') return this.__stat.ctimeMs;
        if (units == 'ns') return this.__stat.ctimeNs;
    }

    isNewer(file) {
        return this.changedAt('ms') > file.changedAt('ms');
    }

    isOlder(file) {
        return this.changedAt('ms') < file.changedAt('ms');
    }

    isDir() {
        return this.__stat.isDirectory();
    }

    isFile() {
        return this.__stat.isFile();
    }

    getType() {
        if ( ! this.exists()) return self::WRONG;
        if (this.isDir()) return self::DIR;
        if (this.isFile()) return self::FILE;
        return self::WRONG;
    }

    rename(newName) {
        return this.moveTo(this.__parentDirPath, newName);
    }

    moveTo(dir, newName = null) {
        if (newName === null) {
            newName = this.name;
        }

        var dirPath = null;
        if (dir.isString) {
            dirPath = dir;
        } else if (dir instanceof lx.Directory) {
            dirPath = dir.path;
        } else return false;

        var newPath = dirPath + '/' + newName;
        if (lx.node.fs.existsSync(newPath)) return false;

        var oldPath = this.path;

        try {
            lx.node.fs.renameSync(oldPath, newPath)
        } catch (e) {
            return false;
        }

        this.__path = newPath;
        this.__name = newName;
        this.__parentDirPath = dirPath;

        return true;
    }

    toFileOrDir() {
        var type = this.getType();
        switch (type) {
            case self::WRONG : return null;
            case self::DIR   : return new lx.Directory(this.path);
            case self::FILE  : return new lx.File(this.path);
        }
        return null;
    }
}
