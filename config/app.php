<?php
// Cấu hình chung. Đọc bằng app_config('tên'), ví dụ app_config('debug').
return [
    'name'     => 'Quản lý kết quả rèn luyện sinh viên',
    'timezone' => 'Asia/Ho_Chi_Minh',
    // true  = hiện chi tiết lỗi và bảng "tài khoản thử" ở trang đăng nhập (khi đang làm đồ án)
    // false = ẩn đi (khi nộp bài / triển khai thật)
    'debug'    => true,
];
