<?php
// Đăng nhập, đăng xuất, thông tin người đang đăng nhập (lưu trong session).
class Auth
{
    // Hash giả: để thời gian xử lý như nhau dù tài khoản có tồn tại hay không
    const DUMMY_HASH = '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S';

    // Kiểm tra tài khoản + mật khẩu. Đúng: trả về dòng users. Sai: trả về chuỗi thông báo lỗi.
    public static function attempt($username, $password)
    {
        $user = User::findByUsername($username);
        $ok = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);

        if ($user === null || !$ok) {
            return 'Sai tài khoản hoặc mật khẩu.';   // không nói rõ sai cái nào (tránh bị dò tài khoản)
        }
        if ($user['status'] !== 'active') {
            return 'Tài khoản chưa được kích hoạt hoặc đã bị khóa. Vui lòng liên hệ quản trị viên.';
        }
        return $user;
    }

    // Ghi thông tin vào session sau khi đăng nhập thành công
    public static function login($user)
    {
        session_regenerate_id(true);   // đổi mã phiên: chống kẻ xấu cài sẵn mã phiên (session fixation)
        $_SESSION['user'] = [
            'id'        => (int) $user['id'],
            'username'  => $user['username'],
            'full_name' => $user['full_name'],
            'role'      => $user['role_code'],
        ];
        User::touchLastLogin($user['id']);
    }

    // Đăng xuất: xóa sạch session
    public static function logout()
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    public static function check()
    {
        return isset($_SESSION['user']);
    }

    public static function user()
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role()
    {
        return $_SESSION['user']['role'] ?? null;
    }

    // Trang chủ của một vai trò, ví dụ homeFor('admin') -> 'admin/dashboard'
    public static function homeFor($role)
    {
        return ROLE_HOME[$role] ?? 'auth/login';
    }
}
