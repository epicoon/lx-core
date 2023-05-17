let _x, _y;

#lx:namespace lx;
class Mouse extends lx.AppComponent {
    get x() { return _x; }
    get y() { return _y; }

    onReady() {
        document.body.addEventListener('mousemove', (e) => {
            _x = e.clientX;
            _y = e.clientY;
        });
    }
}
