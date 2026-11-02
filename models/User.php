<?php
// Làm việc với bảng users. Model chỉ chứa câu SQL.
class User
{
    // Tìm người dùng theo tên đăng nhập, kèm mã vai trò (cột role_code)
    public static function findByUsername($username)
    {
        $st = Database::pdo()->prepare(
            'SELECT u.*, r.code AS role_code
               FROM users u
               JOIN roles r ON r.id = u.role_id
              WHERE u.username = ?
              LIMIT 1'
        );
        $st->execute([$username]);
        $row = $st->fetch();
        return $row ?: null;
    }

    // Ghi lại thời điểm đăng nhập gần nhất
    public static function touchLastLogin($id)
    {
        $st = Database::pdo()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?');
        $st->execute([date('Y-m-d H:i:s'), $id]);
    }
}
