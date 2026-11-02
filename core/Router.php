<?php
// Đổi địa chỉ thành lời gọi hàm:  /controller/hanh-dong/tham-so
//   /auth/login          -> AuthController::login()
//   /assessment/form/2   -> AssessmentController::form(2)
//   /criteria/save-item  -> CriteriaController::saveItem()
// Chỉ chạy được hàm PUBLIC của lớp con Controller. Còn lại trả 404.
class Router
{
    // Địa chỉ yêu cầu hiện tại, ví dụ "admin/dashboard" (do public/.htaccess truyền vào ?url=)
    public static function path()
    {
        return trim($_GET['url'] ?? '', '/');
    }

    public static function dispatch()
    {
        $path = self::path();

        // Vào trang chủ "/": đã đăng nhập thì về trang của vai trò, chưa thì sang trang đăng nhập
        if ($path === '' || $path === 'index.php') {
            header('Location: ' . url(Auth::check() ? Auth::homeFor(Auth::role()) : 'auth/login'));
            return;
        }

        $parts = explode('/', $path);
        foreach ($parts as $p) {
            if (!preg_match('/^[A-Za-z0-9-]+$/', $p)) {
                self::notFound();
            }
        }

        $class  = self::studly($parts[0]) . 'Controller';        // "auth" -> "AuthController"
        $method = lcfirst(self::studly($parts[1] ?? 'index'));   // "save-item" -> "saveItem"
        $args   = array_slice($parts, 2);                        // phần còn lại là tham số
        foreach ($args as $i => $a) {
            if (ctype_digit($a)) {
                $args[$i] = (int) $a;                            // "2" -> 2
            }
        }

        if (!is_file(BASE_PATH . "/controllers/$class.php") || !class_exists($class)
            || !is_subclass_of($class, 'Controller')) {
            self::notFound();
        }
        $controller = new $class();
        if (!is_callable([$controller, $method]) || strpos($method, '__') === 0) {
            self::notFound();
        }

        // Số tham số và kiểu (int) phải khớp với hàm, nếu không là địa chỉ sai -> 404
        $rm = new ReflectionMethod($controller, $method);
        if (count($args) > $rm->getNumberOfParameters() || count($args) < $rm->getNumberOfRequiredParameters()) {
            self::notFound();
        }
        foreach ($rm->getParameters() as $i => $param) {
            $type = $param->getType();
            if (isset($args[$i]) && $type && $type->getName() === 'int' && !is_int($args[$i])) {
                self::notFound();
            }
        }
        $controller->$method(...$args);
    }

    private static function studly($s)
    {
        return str_replace(' ', '', ucwords(str_replace('-', ' ', $s)));
    }

    private static function notFound()
    {
        http_response_code(404);
        render_view('errors/404', ['pageTitle' => 'Không tìm thấy trang'], !Auth::check());
        exit;
    }
}
