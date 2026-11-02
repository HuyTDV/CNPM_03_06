// Trang khung điểm: điền sẵn các hộp thoại thêm/sửa từ thuộc tính data-* của nút bấm.
(function () {
    'use strict';
    function camel(name) { return name.replace(/_([a-z])/g, function (_, c) { return c.toUpperCase(); }); }

    document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.addEventListener('show.bs.modal', function (ev) {
            var btn = ev.relatedTarget;
            var form = modalEl.querySelector('form');
            if (!btn || !form) { return; }
            form.reset();
            form.querySelectorAll('[name]').forEach(function (input) {
                if (input.name === '_csrf') { return; }
                var value = btn.dataset[camel(input.name)];
                if (input.type === 'checkbox') {
                    input.checked = value === '1';
                } else if (value !== undefined) {
                    input.value = value;
                } else if (input.type === 'hidden') {
                    input.value = '';        // ô ẩn không có trong data-* (vd. id khi thêm mới) phải xóa trắng
                }
            });
            var title = modalEl.querySelector('[data-modal-title]');
            if (title && btn.dataset.modalTitle) { title.textContent = btn.dataset.modalTitle; }
        });
    });
})();
