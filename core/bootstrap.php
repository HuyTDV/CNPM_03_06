<?php
// File này chạy ĐẦU TIÊN ở mọi request (được public/index.php gọi).
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/constants.php';
$GLOBALS['app'] = require BASE_PATH . '/config/app.php';

require BASE_PATH . '/helpers/view.php';
require BASE_PATH . '/helpers/session.php';
require BASE_PATH . '/helpers/csrf.php';
require BASE_PATH . '/helpers/format.php';
require BASE_PATH . '/helpers/score.php';
require BASE_PATH . '/helpers/upload.php';

// Tự nạp class: gọi "new User" thì PHP tự tìm file User.php trong core/, controllers/, models/
spl_autoload_register(function ($class) {
    foreach (['core', 'controllers', 'models'] as $dir) {
        $file = BASE_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

date_default_timezone_set(app_config('timezone', 'Asia/Ho_Chi_Minh'));
error_reporting(E_ALL);
ini_set('display_errors', app_config('debug') ? '1' : '0');

// Mọi lỗi không bắt được sẽ hiện trang 500 thân thiện (khi debug=true có thêm chi tiết lỗi)
set_exception_handler(function ($e) {
    error_log((string) $e);
    http_response_code(500);
    render_view('errors/500', [
        'pageTitle' => 'Lỗi hệ thống',
        'detail'    => app_config('debug') ? $e->getMessage() : '',
    ], true);
});

start_session();
