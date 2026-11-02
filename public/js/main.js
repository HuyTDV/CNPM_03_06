// JavaScript dùng chung (đơn giản)
(function () {
    'use strict';

    // Nút Hiện/Ẩn mật khẩu
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.querySelector(btn.getAttribute('data-toggle-password'));
            if (!input) { return; }
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Ẩn' : 'Hiện';
        });
    });

    // Bấm vào "tài khoản thử" để điền sẵn vào form đăng nhập
    document.querySelectorAll('[data-fill-user]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('username').value = btn.getAttribute('data-fill-user');
            document.getElementById('password').value = btn.getAttribute('data-fill-pass');
            document.getElementById('password').focus();
        });
    });

    // Thông báo thành công tự biến mất sau 5 giây
    document.querySelectorAll('.alert-success').forEach(function (el) {
        setTimeout(function () {
            if (window.bootstrap) { bootstrap.Alert.getOrCreateInstance(el).close(); }
        }, 5000);
    });
})();
