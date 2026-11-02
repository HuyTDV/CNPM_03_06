<?php
// Hàm hỗ trợ giao diện.

// Đọc cấu hình trong config/app.php
function app_config($key, $default = null)
{
    return $GLOBALS['app'][$key] ?? $default;
}

// Escape HTML. LUÔN dùng khi in dữ liệu ra trang để chống XSS (viết: echo e($ten))
function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Thư mục gốc của dự án trên địa chỉ web, ví dụ "/CNPM_DoAn" (tự nhận, không cần cấu hình)
function base_path()
{
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if (substr($dir, -7) === '/public') {
        $dir = substr($dir, 0, -7);
    }
    return $dir;
}

// Địa chỉ trang: url('auth/login') -> /CNPM_DoAn/auth/login
function url($path = '')
{
    return base_path() . '/' . ltrim($path, '/');
}

// Địa chỉ file tĩnh trong public/ (thêm ?v=... để trình duyệt tải lại khi file đổi)
function asset($path)
{
    $file = BASE_PATH . '/public/' . ltrim($path, '/');
    return url($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

// Chữ cái đầu cho ảnh đại diện: "Nguyễn Minh Anh" -> "NA"
function initials($name)
{
    $words = preg_split('/\s+/u', trim((string) $name));
    $first = mb_substr($words[0] ?? '', 0, 1);
    $last  = count($words) > 1 ? mb_substr(end($words), 0, 1) : '';
    return mb_strtoupper($first . $last);
}

// Hiển thị một trang: ghép header + nội dung + footer.
// $bare = true: trang trống không có menu (đăng nhập, lỗi khi chưa đăng nhập).
function render_view($__file, $__data = [], $bare = false)
{
    extract($__data, EXTR_SKIP);
    require BASE_PATH . '/views/layouts/header.php';
    require BASE_PATH . '/views/' . $__file . '.php';
    require BASE_PATH . '/views/layouts/footer.php';
}
