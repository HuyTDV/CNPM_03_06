// Form đợt đánh giá: lọc khung điểm theo học kỳ, kiểm tra thứ tự 4 mốc thời gian.
(function () {
    'use strict';
    var semester = document.getElementById('semester_id');
    var framework = document.getElementById('framework_id');

    function filterFrameworks() {
        if (!semester || !framework) { return; }
        Array.prototype.forEach.call(framework.options, function (opt) {
            if (!opt.value) { return; }
            var ok = opt.dataset.semester === semester.value;
            opt.hidden = !ok;
            opt.disabled = !ok;
            if (!ok && opt.selected) { framework.value = ''; }
        });
    }
    if (semester) { semester.addEventListener('change', filterFrameworks); }
    filterFrameworks();

    var ids = ['start_at', 'student_end_at', 'class_end_at', 'advisor_end_at'];
    var labels = ['Bắt đầu', 'Hạn sinh viên nộp', 'Hạn lớp chấm', 'Hạn GVCN duyệt'];
    function checkOrder() {
        ids.forEach(function (id) { document.getElementById(id).setCustomValidity(''); });
        for (var i = 1; i < ids.length; i++) {
            var prev = document.getElementById(ids[i - 1]);
            var cur = document.getElementById(ids[i]);
            // chuỗi "YYYY-MM-DDTHH:mm" so sánh theo chữ cái cũng đúng thứ tự thời gian
            if (prev.value && cur.value && cur.value < prev.value) {
                cur.setCustomValidity('"' + labels[i] + '" phải sau hoặc bằng "' + labels[i - 1] + '".');
                return;
            }
        }
    }
    ids.forEach(function (id) { document.getElementById(id).addEventListener('input', checkOrder); });
    checkOrder();
})();
