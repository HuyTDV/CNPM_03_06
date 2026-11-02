<?php
// Lớp cha của mọi controller. Các hàm ở đây đều protected nên không gọi được bằng địa chỉ web.
// LƯU Ý: đừng đặt tên hàm hành động (public) trùng các tên: render, renderRaw, redirect, abort, requireLogin, requireRole, requirePost, post.
class Controller
{
    // Hiển thị một trang trong thư mục views/
    protected function render($file, $data = [], $bare = false)
    {
        render_view($file, $data, $bare);
    }

    // Hiển thị một file view đứng riêng (không header/menu), dùng cho tài liệu in hoặc tải về
    protected function renderRaw($file, $data = [])
    {
        extract($data, EXTR_SKIP);
        require BASE_PATH . '/views/' . $file . '.php';
    }

    // Chuyển sang trang khác
    protected function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }

    // Hiện trang lỗi (403, 404...) rồi dừng
    protected function abort($code, $message = '')
    {
        http_response_code($code);
        $view = in_array($code, [403, 404], true) ? "errors/$code" : 'errors/403';
        render_view($view, ['pageTitle' => 'Lỗi ' . $code, 'message' => $message], !Auth::check());
        exit;
    }

    // Bắt buộc đã đăng nhập. Chưa thì chuyển về trang đăng nhập.
    protected function requireLogin()
    {
        if (!Auth::check()) {
            flash('warning', 'Vui lòng đăng nhập để tiếp tục.');
            $this->redirect('auth/login');
        }
        return Auth::user();
    }

    // Bắt buộc đã đăng nhập VÀ có một trong các vai trò. Sai vai trò -> trang 403.
    // Gọi ở dòng ĐẦU TIÊN của mọi hàm cần bảo vệ:  $this->requireRole([ROLE_ADMIN]);
    protected function requireRole($roles)
    {
        $user = $this->requireLogin();
        if (!in_array($user['role'], $roles, true)) {
            $this->abort(403, 'Trang này dành cho vai trò khác. Hãy quay về trang của bạn nhé.');
        }
        return $user;
    }

    // Lấy một ô của form POST dưới dạng chuỗi đã cắt khoảng trắng đầu/cuối ('' nếu không có)
    protected function post($key)
    {
        $v = $_POST[$key] ?? '';
        return is_string($v) ? trim($v) : '';
    }

    // Chỉ chấp nhận POST và phải có token CSRF hợp lệ (dùng cho đăng xuất, lưu dữ liệu...)
    protected function requirePost()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->abort(405, 'Yêu cầu không hợp lệ.');
        }
        if (!csrf_verify()) {
            $this->abort(403, 'Phiên làm việc đã hết hạn. Hãy tải lại trang rồi thử lại.');
        }
    }
}
