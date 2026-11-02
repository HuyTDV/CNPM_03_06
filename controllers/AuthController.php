<?php
// Đăng nhập và đăng xuất.
class AuthController extends Controller
{
    // Địa chỉ: /auth/login
    //   Mở trang (GET)  -> hiện form đăng nhập
    //   Bấm nút (POST)  -> kiểm tra tài khoản, đúng thì vào trang của vai trò
    public function login()
    {
        // Đã đăng nhập rồi thì không cần thấy form nữa
        if (Auth::check()) {
            $this->redirect(Auth::homeFor(Auth::role()));
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!csrf_verify()) {
                flash('danger', 'Phiên làm việc đã hết hạn. Vui lòng thử lại.');
                $this->redirect('auth/login');
            }

            $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

            if ($username === '' || $password === '') {
                set_old_username($username);
                flash('danger', 'Vui lòng nhập đầy đủ tài khoản và mật khẩu.');
                $this->redirect('auth/login');
            }

            $result = Auth::attempt($username, $password);
            if (is_string($result)) {              // chuỗi = thông báo lỗi
                set_old_username($username);
                flash('danger', $result);
                $this->redirect('auth/login');
            }

            Auth::login($result);
            $this->redirect(Auth::homeFor(Auth::role()));
        }

        $this->render('auth/login', ['pageTitle' => 'Đăng nhập'], true);
    }

    // Địa chỉ: /auth/logout (chỉ nhận POST từ nút "Đăng xuất")
    public function logout()
    {
        $this->requirePost();
        Auth::logout();
        flash('success', 'Bạn đã đăng xuất. Hẹn gặp lại!');
        $this->redirect('auth/login');
    }
}
