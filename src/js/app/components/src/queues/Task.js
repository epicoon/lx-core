#lx:namespace lx;
class Task {
    #lx:const
        STATUS_NEW = 1,
        STATUS_PENDING = 2,
        STATUS_COMPLETED = 3;

    constructor(queue = null, callback = null) {
        this.callback = null;
        this._onChangeStatus = null;
        this.status = self::STATUS_NEW;
        this.setCallback(callback);
        if (queue === null) queue = '_lxstd_';
        if (queue) this.setQueue(queue);
    }

    setQueue(queue) {
        if (lx.isString(queue)) lx.app.queues.add(queue, this);
        else this.queue = queue;
        return this;
    }

    setCallback(callback) {
        this.callback = callback;
        return this;
    }

    onChangeStatus(callback) {
        this._onChangeStatus = callback;
    }

    run() {
        this.setPending();
        if (this.callback) this.callback.call(this);
    }

    isStatus(status) {
        return this.status == status;
    }

    isNew() {
        return this.isStatus(self::STATUS_NEW);
    }

    isPending() {
        return this.isStatus(self::STATUS_PENDING);
    }

    isCompleted() {
        return this.isStatus(self::STATUS_COMPLETED);
    }

    setStatus(status) {
        this.status = status;
        if (this._onChangeStatus)
            this._onChangeStatus();
    }

    setPending() {
        this.setStatus(self::STATUS_PENDING);
    }

    setCompleted() {
        this.setStatus(self::STATUS_COMPLETED);
    }
}
