// Trang GVCN duyệt lớp: chọn tất cả và đếm số sinh viên đã chọn.
(function () {
    'use strict';
    var all = document.getElementById('pickAll');
    var counter = document.getElementById('pickCount');
    var picks = document.querySelectorAll('.pick');
    if (!all || !counter) { return; }

    function update() {
        var n = 0;
        picks.forEach(function (c) { if (c.checked) { n++; } });
        counter.textContent = n;
        all.checked = picks.length > 0 && n === picks.length;
    }
    all.addEventListener('change', function () {
        picks.forEach(function (c) { c.checked = all.checked; });
        update();
    });
    picks.forEach(function (c) { c.addEventListener('change', update); });
    update();
})();
