// Form tự đánh giá và form chấm/duyệt (assessmentForm, reviewForm): tự cộng điểm từng điều và tổng, kiểm tra nhanh file minh chứng.
// Đây chỉ hỗ trợ hiển thị; server mới là nơi kiểm tra và tính tổng chính thức.
(function () {
    'use strict';
    var form = document.getElementById('assessmentForm') || document.getElementById('reviewForm');
    if (!form) { return; }

    function num(v) {
        var n = parseFloat(String(v || '').replace(',', '.'));
        return isNaN(n) ? null : n;
    }
    function fmt(n) { return String(Math.round(n * 100) / 100).replace('.', ','); }

    function recalc() {
        var grand = 0;
        form.querySelectorAll('.score-category').forEach(function (cat) {
            var sum = 0;
            cat.querySelectorAll('.score-input').forEach(function (input) {
                var n = num(input.value);
                if (n === null) { input.classList.remove('is-invalid'); return; }
                sum += n;
                input.classList.toggle('is-invalid', n < parseFloat(input.min) || n > parseFloat(input.max));
            });
            cat.querySelector('.cat-sum').textContent = fmt(sum);
            cat.classList.toggle('over-max', sum > parseFloat(cat.dataset.max) + 0.0001);
            grand += sum;
        });
        document.getElementById('grandTotal').textContent = fmt(grand);
    }
    form.addEventListener('input', function (ev) {
        if (ev.target.classList.contains('score-input')) { recalc(); }
    });
    recalc();

    // Chọn file: báo ngay nếu sai đuôi, quá nặng hoặc quá số lượng
    form.querySelectorAll('.upload-input').forEach(function (input) {
        input.addEventListener('change', function () {
            var msg = input.parentElement.querySelector('.upload-msg');
            var remaining = parseInt(input.dataset.remaining, 10);
            var maxSize = parseInt(input.dataset.maxSize, 10);
            var error = '';
            if (input.files.length > remaining) { error = 'Chỉ được thêm tối đa ' + remaining + ' file cho khoản này.'; }
            Array.prototype.forEach.call(input.files, function (f) {
                var ext = f.name.split('.').pop().toLowerCase();
                if (!error && ['pdf', 'jpg', 'jpeg', 'png'].indexOf(ext) === -1) { error = '"' + f.name + '": chỉ nhận PDF, JPG, PNG.'; }
                if (!error && f.size > maxSize) { error = '"' + f.name + '" vượt quá ' + Math.round(maxSize / 1048576) + ' MB.'; }
            });
            if (error) { input.value = ''; }
            if (msg) { msg.textContent = error; }
        });
    });
})();
