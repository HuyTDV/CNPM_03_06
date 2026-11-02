<?php
// Các hằng số dùng chung. Mã vai trò PHẢI khớp cột roles.code trong CSDL.
const ROLE_STUDENT = 'student';
const ROLE_MONITOR = 'monitor';
const ROLE_ADVISOR = 'advisor';
const ROLE_ADMIN   = 'admin';

// Tên hiển thị của vai trò
const ROLE_LABELS = [
    ROLE_STUDENT => 'Sinh viên',
    ROLE_MONITOR => 'Cán bộ lớp',
    ROLE_ADVISOR => 'Giáo viên chủ nhiệm',
    ROLE_ADMIN   => 'Quản trị viên',
];

// Trang chủ của từng vai trò (sau khi đăng nhập sẽ chuyển tới đây)
const ROLE_HOME = [
    ROLE_STUDENT => 'student/dashboard',
    ROLE_MONITOR => 'class/dashboard',
    ROLE_ADVISOR => 'advisor/dashboard',
    ROLE_ADMIN   => 'admin/dashboard',
];

// ----- Trạng thái bài đánh giá (khớp cột evaluations.status) -----
const EVAL_DRAFT            = 'draft';
const EVAL_SUBMITTED        = 'submitted';
const EVAL_CLASS_GRADED     = 'class_graded';
const EVAL_NEED_EDIT        = 'need_edit';
const EVAL_ADVISOR_APPROVED = 'advisor_approved';
const EVAL_FINAL            = 'final';

const EVAL_LABELS = [
    EVAL_DRAFT            => 'Bản nháp',
    EVAL_SUBMITTED        => 'Đã nộp, chờ lớp chấm',
    EVAL_CLASS_GRADED     => 'Lớp đã chấm, chờ GVCN',
    EVAL_NEED_EDIT        => 'Cần chỉnh sửa',
    EVAL_ADVISOR_APPROVED => 'GVCN đã duyệt',
    EVAL_FINAL            => 'Đã chốt',
];
// Màu nhãn (tên màu của Bootstrap)
const EVAL_COLORS = [
    EVAL_DRAFT            => 'secondary',
    EVAL_SUBMITTED        => 'info',
    EVAL_CLASS_GRADED     => 'primary',
    EVAL_NEED_EDIT        => 'warning',
    EVAL_ADVISOR_APPROVED => 'success',
    EVAL_FINAL            => 'success',
];
// Sinh viên chỉ thấy điểm chính thức khi bài ở các trạng thái này
const EVAL_RESULT_VISIBLE = [EVAL_ADVISOR_APPROVED, EVAL_FINAL];

// ----- Đợt đánh giá (khớp cột evaluation_periods.status) -----
const PERIOD_OPEN   = 'open';
const PERIOD_LOCKED = 'locked';

// Giai đoạn hiện tại của đợt: tính bằng PHP từ trạng thái + 4 mốc thời gian (Period::phase)
const PHASE_LABELS = [
    'not_started' => 'Chưa bắt đầu',
    'student'     => 'Sinh viên đang nộp',
    'class'       => 'Lớp đang chấm',
    'advisor'     => 'GVCN đang duyệt',
    'ended'       => 'Đã hết hạn',
    'locked'      => 'Đã khóa',
];
const PHASE_COLORS = [
    'not_started' => 'secondary',
    'student'     => 'success',
    'class'       => 'primary',
    'advisor'     => 'info',
    'ended'       => 'dark',
    'locked'      => 'danger',
];

// ----- Minh chứng -----
const MAX_EVIDENCE_PER_ITEM = 5;                 // số file tối đa cho mỗi tiêu chí
const MAX_UPLOAD_BYTES      = 5 * 1024 * 1024;   // 5 MB mỗi file
const UPLOAD_TYPES = [                           // đuôi file cho phép => loại nội dung thật
    'pdf'  => 'application/pdf',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
];

// ----- Xếp loại rèn luyện: [điểm tối thiểu, tên], sắp theo điểm giảm dần -----
// CẦN đối chiếu với quy chế đánh giá rèn luyện chính thức của trường.
const RATINGS = [
    [90, 'Xuất sắc'],
    [80, 'Tốt'],
    [65, 'Khá'],
    [50, 'Trung bình'],
    [35, 'Yếu'],
    [0,  'Kém'],
];
