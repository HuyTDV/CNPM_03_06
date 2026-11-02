<?php
// Menu bên trái theo vai trò. Mỗi mục: [nhãn, đường dẫn, tiền tố để tô sáng mục đang xem].
// Tiền tố có thể gồm nhiều giá trị ngăn bằng dấu | (mục sáng khi đang ở một trong các trang đó).
// Đường dẫn = null nghĩa là chức năng "sắp có" (hiện mờ, chưa bấm được).
// Khi làm xong một chức năng: thay null bằng đường dẫn thật.
$studentMenu = [
    ['Tổng quan',       'student/dashboard',  'student'],
    ['Tự đánh giá',     'assessment/current', 'assessment'],
    ['Kết quả của tôi', null, ''],
    ['Khiếu nại',       null, ''],
];

return [
    ROLE_STUDENT => $studentMenu,
    // Cán bộ lớp cũng là sinh viên nên có thêm các mục của sinh viên (đổi tên mục đầu để khỏi trùng "Tổng quan")
    ROLE_MONITOR => array_merge([
        ['Cán bộ lớp',       'class/dashboard', 'class/dashboard'],
        ['Chấm điểm lớp',    'class/current',   'class/students|class/grade'],
        ['Biên bản họp lớp', 'minute/current', 'minute'],
        ['Trang sinh viên',  'student/dashboard', 'student'],
    ], array_slice($studentMenu, 1)),
    ROLE_ADVISOR => [
        ['Tổng quan',      'advisor/dashboard', 'advisor/dashboard'],
        ['Duyệt điểm lớp', 'advisor/current', 'advisor/review|advisor/student'],
    ],
    ROLE_ADMIN => [
        ['Tổng quan',             'admin/dashboard', 'admin'],
        ['Khung điểm & tiêu chí', 'criteria',        'criteria'],
        ['Đợt đánh giá',          'period',          'period'],
        ['Tài khoản',             null, ''],
        ['Thống kê & báo cáo',    null, ''],
    ],
];
