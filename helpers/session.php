<?php
// Session (phiên làm việc) và thông báo flash.

function start_session()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('RLSESSID');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,                    // hết hạn khi đóng trình duyệt
        'path'     => base_path() . '/',
        'httponly' => true,                 // JavaScript không đọc được cookie phiên
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Thông báo hiện MỘT LẦN ở trang kế tiếp. $type: success | danger | warning | info
function flash($type, $message)
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

// Lấy và xóa toàn bộ thông báo (được views/partials/flash.php gọi)
function flash_pull()
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

// Nhớ tên đăng nhập vừa gõ để điền lại vào ô khi đăng nhập sai
function set_old_username($username)
{
    $_SESSION['old_username'] = $username;
}

function pull_old_username()
{
    $v = $_SESSION['old_username'] ?? '';
    unset($_SESSION['old_username']);
    return $v;
}
