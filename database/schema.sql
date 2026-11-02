-- ========================================================
-- FILE      : database/schema.sql
-- MÔ TẢ     : Tạo CSDL và các bảng (TS1-02). CHỈ CÓ CẤU TRÚC BẢNG.
-- HỆ QUẢN TRỊ: MySQL 5.7+ / MariaDB 10.2+ (XAMPP dùng được)
-- CÁCH CHẠY : phpMyAdmin > Import, hoặc: mysql -u root < database/schema.sql
--             Sau đó chạy tiếp database/seed.sql để có dữ liệu mẫu.
-- --------------------------------------------------------
-- NGUYÊN TẮC: CSDL chỉ LƯU DỮ LIỆU. Không có view, trigger, procedure, function,
--             cột tự tính, CHECK. Mọi phép tính và quy tắc nghiệp vụ nằm trong code PHP:
--             tổng điểm, xếp loại, điểm trong [min,max], mỗi học kỳ chỉ 1 khung điểm
--             đang bật, thứ tự 4 mốc thời gian, lý do bắt buộc khi yêu cầu chỉnh sửa,
--             chuyển trạng thái đúng luồng, còn hạn nộp/chấm/duyệt...
--             Phần CSDL giữ lại chỉ là khóa chính, khóa ngoại, UNIQUE và chỉ mục
--             (để dữ liệu không mồ côi, không trùng và truy vấn nhanh).
-- --------------------------------------------------------
-- CẢNH BÁO  : File này XÓA RỒI TẠO LẠI toàn bộ bảng (DROP TABLE IF EXISTS)
--             để cả nhóm import lại được nhiều lần khi đổi cấu trúc.
--             KHÔNG chạy trên CSDL đã có dữ liệu thật.
-- --------------------------------------------------------
-- QUY ƯỚC  :
--  - Khóa chính: id INT UNSIGNED AUTO_INCREMENT. Bảng/cột: snake_case.
--  - Giá trị ENUM của trạng thái khớp config/constants.php.
--  - Điểm dùng DECIMAL(5,2) (tránh sai số số thực).
--  - Giờ lưu theo múi giờ máy chủ; PHP đặt Asia/Ho_Chi_Minh trong core/bootstrap.php.
-- ========================================================

CREATE DATABASE IF NOT EXISTS ql_ren_luyen CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ql_ren_luyen;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Xóa bảng cũ (thứ tự ngược với thứ tự tạo)
-- --------------------------------------------------------
-- Dọn các đối tượng của phiên bản schema cũ (nếu đã từng import)
DROP VIEW  IF EXISTS v_evaluation_results;
DROP TABLE IF EXISTS revoked_tokens;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS class_minutes;
DROP TABLE IF EXISTS complaints;
DROP TABLE IF EXISTS approvals;
DROP TABLE IF EXISTS evidences;
DROP TABLE IF EXISTS evaluation_scores;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS evaluation_periods;
DROP TABLE IF EXISTS criteria_items;
DROP TABLE IF EXISTS criteria_categories;
DROP TABLE IF EXISTS criteria_frameworks;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS semesters;
DROP TABLE IF EXISTS faculties;
DROP TABLE IF EXISTS roles;

-- ========================================================
-- NHÓM 1: DANH MỤC VÀ TÀI KHOẢN
-- ========================================================

-- 1. roles : 4 vai trò. `code` khớp hằng ROLE_* trong config/constants.php
CREATE TABLE roles (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code        VARCHAR(20)  NOT NULL COMMENT 'student | monitor | advisor | admin',
    name        VARCHAR(100) NOT NULL COMMENT 'Tên hiển thị',
    description VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Vai trò người dùng';

-- 2. faculties : Khoa
CREATE TABLE faculties (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(20)  NOT NULL,
    name       VARCHAR(150) NOT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_faculties_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Khoa';

-- 3. semesters : Học kỳ / năm học
CREATE TABLE semesters (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    academic_year VARCHAR(9)       NOT NULL COMMENT 'Năm học, ví dụ 2026-2027',
    term          TINYINT UNSIGNED NOT NULL COMMENT 'Học kỳ: 1, 2, 3 (hè)',
    name          VARCHAR(100)     NOT NULL COMMENT 'Ví dụ: Học kỳ 1 năm học 2026-2027',
    start_date    DATE             NOT NULL,
    end_date      DATE             NOT NULL,
    is_current    TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '1 = học kỳ hiện hành',
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_semesters_year_term (academic_year, term)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Học kỳ / năm học';

-- 4. users : tài khoản đăng nhập (cả 4 vai trò). Họ tên lưu ở đây cho mọi vai trò.
CREATE TABLE users (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username                VARCHAR(50)  NOT NULL COMMENT 'Mã đăng nhập: SV dùng mã sinh viên, GVCN/Admin dùng mã cán bộ',
    email                   VARCHAR(150) NULL,
    password_hash           VARCHAR(255) NOT NULL COMMENT 'Băm bằng password_hash() của PHP',
    full_name               VARCHAR(100) NOT NULL,
    phone                   VARCHAR(20)  NULL,
    role_id                 INT UNSIGNED NOT NULL,
    status                  ENUM('pending','active','locked') NOT NULL DEFAULT 'active'
                            COMMENT 'pending = chờ kích hoạt, active = dùng được, locked = bị khóa (không đăng nhập được)',
    must_change_password    TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '1 = bắt buộc đổi mật khẩu ở lần đăng nhập đầu',
    created_by              INT UNSIGNED NULL COMMENT 'Người đã tạo tài khoản này (cấp cao hơn); NULL = tài khoản gốc từ seed',
    last_login_at           DATETIME     NULL,
    reset_token_hash        CHAR(64)     NULL COMMENT 'SHA-256 của token đặt lại mật khẩu',
    reset_token_expires_at  DATETIME     NULL,
    created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role_id),
    KEY idx_users_created_by (created_by),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tài khoản đăng nhập. Quy tắc ai được tạo ai do PHP kiểm tra, CSDL chỉ lưu created_by';

-- 5. classes : Lớp. GVCN là users (role advisor); cán bộ lớp là students (FK thêm ở cuối file
--    vì students chưa tồn tại tại thời điểm này - tham chiếu vòng).
CREATE TABLE classes (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code       VARCHAR(30)  NOT NULL COMMENT 'Mã lớp',
    name       VARCHAR(150) NOT NULL,
    faculty_id INT UNSIGNED NOT NULL,
    cohort     VARCHAR(20)  NOT NULL COMMENT 'Khóa, ví dụ K64 (lọc thống kê theo khóa)',
    advisor_id INT UNSIGNED NULL COMMENT 'GVCN / cố vấn học tập (users, vai trò advisor)',
    monitor_id INT UNSIGNED NULL COMMENT 'Cán bộ lớp (students) - FK thêm ở cuối file',
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_classes_code (code),
    KEY idx_classes_faculty (faculty_id),
    KEY idx_classes_cohort (cohort),
    KEY idx_classes_advisor (advisor_id),
    CONSTRAINT fk_classes_faculty FOREIGN KEY (faculty_id) REFERENCES faculties (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_classes_advisor FOREIGN KEY (advisor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lớp';

-- 6. students : hồ sơ sinh viên (1-1 với users). Cán bộ lớp cũng là sinh viên.
CREATE TABLE students (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id       INT UNSIGNED NOT NULL,
    student_code  VARCHAR(20)  NOT NULL COMMENT 'Mã sinh viên',
    class_id      INT UNSIGNED NOT NULL,
    date_of_birth DATE         NULL,
    gender        ENUM('male','female','other') NULL,
    status        ENUM('studying','suspended','graduated') NOT NULL DEFAULT 'studying',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_user (user_id),
    UNIQUE KEY uq_students_code (student_code),
    KEY idx_students_class (class_id),
    CONSTRAINT fk_students_user  FOREIGN KEY (user_id)  REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_students_class FOREIGN KEY (class_id) REFERENCES classes (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Hồ sơ sinh viên';

-- Hoàn tất tham chiếu vòng classes.monitor_id -> students.id
ALTER TABLE classes
    ADD KEY idx_classes_monitor (monitor_id),
    ADD CONSTRAINT fk_classes_monitor FOREIGN KEY (monitor_id) REFERENCES students (id)
        ON UPDATE CASCADE ON DELETE SET NULL;

-- ========================================================
-- NHÓM 2: KHUNG ĐIỂM VÀ TIÊU CHÍ (US-07)
-- ========================================================

-- 7. criteria_frameworks : khung điểm áp dụng cho 1 học kỳ
--    PHP bảo đảm mỗi học kỳ chỉ có tối đa 1 khung is_active = 1 (bật khung mới thì tắt khung cũ).
CREATE TABLE criteria_frameworks (
    id                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    semester_id        INT UNSIGNED  NOT NULL,
    name               VARCHAR(150)  NOT NULL,
    description        VARCHAR(500)  NULL,
    total_max_score    DECIMAL(5,2)  NOT NULL DEFAULT 100.00 COMMENT 'Tổng điểm tối đa của khung (thường 100)',
    is_active          TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = đang kích hoạt, 0 = vô hiệu hóa',
    created_by         INT UNSIGNED  NULL,
    created_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_frameworks_semester (semester_id),
    CONSTRAINT fk_frameworks_semester FOREIGN KEY (semester_id) REFERENCES semesters (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_frameworks_creator FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Khung điểm theo học kỳ';

-- 8. criteria_categories : Điều / nhóm tiêu chí
CREATE TABLE criteria_categories (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    framework_id INT UNSIGNED  NOT NULL,
    code         VARCHAR(10)   NOT NULL COMMENT 'Số điều: 1, 2, 3...',
    title        VARCHAR(255)  NOT NULL,
    description  VARCHAR(500)  NULL,
    max_score    DECIMAL(5,2)  NOT NULL COMMENT 'Điểm tối đa của cả điều',
    sort_order   INT           NOT NULL DEFAULT 0,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_code (framework_id, code),
    KEY idx_categories_sort (framework_id, sort_order),
    CONSTRAINT fk_categories_framework FOREIGN KEY (framework_id) REFERENCES criteria_frameworks (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Điều / nhóm tiêu chí';

-- 9. criteria_items : Khoản / tiêu chí chi tiết
--    Không xóa được tiêu chí đã có sinh viên chấm điểm (FK RESTRICT ở evaluation_scores):
--    API nên bắt lỗi này và báo "Tiêu chí đã được sử dụng, không thể xóa".
CREATE TABLE criteria_items (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    category_id      INT UNSIGNED  NOT NULL,
    code             VARCHAR(15)   NOT NULL COMMENT 'Số khoản: 1.1, 1.2...',
    content          VARCHAR(500)  NOT NULL COMMENT 'Nội dung tiêu chí',
    max_score        DECIMAL(5,2)  NOT NULL,
    min_score        DECIMAL(5,2)  NOT NULL DEFAULT 0 COMMENT 'Âm nếu là mục trừ điểm (vi phạm)',
    require_evidence TINYINT(1)    NOT NULL DEFAULT 0 COMMENT '1 = bắt buộc đính kèm minh chứng',
    sort_order       INT           NOT NULL DEFAULT 0,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_items_code (category_id, code),
    KEY idx_items_sort (category_id, sort_order),
    CONSTRAINT fk_items_category FOREIGN KEY (category_id) REFERENCES criteria_categories (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Khoản / tiêu chí chi tiết';

-- ========================================================
-- NHÓM 3: ĐỢT ĐÁNH GIÁ (US-08)
-- ========================================================

-- 10. evaluation_periods : đợt đánh giá + 4 mốc thời gian
--     PHP bảo đảm: start_at <= student_end_at <= class_end_at <= advisor_end_at
--     status 'locked': đóng thủ công (lock/unlock) hoặc tự khóa khi quá advisor_end_at
--     (cron/auto-lock-periods.php, hoặc kiểm tra thời gian ngay trong code).
CREATE TABLE evaluation_periods (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name           VARCHAR(150) NOT NULL,
    semester_id    INT UNSIGNED NOT NULL,
    framework_id   INT UNSIGNED NOT NULL COMMENT 'Khung điểm áp dụng cho đợt này',
    start_at       DATETIME     NOT NULL COMMENT 'Mốc 1: bắt đầu đợt',
    student_end_at DATETIME     NOT NULL COMMENT 'Mốc 2: hạn sinh viên nộp',
    class_end_at   DATETIME     NOT NULL COMMENT 'Mốc 3: hạn lớp chấm',
    advisor_end_at DATETIME     NOT NULL COMMENT 'Mốc 4: hạn GVCN duyệt',
    status         ENUM('open','locked') NOT NULL DEFAULT 'open',
    locked_at      DATETIME     NULL,
    note           VARCHAR(500) NULL,
    created_by     INT UNSIGNED NULL,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_periods_semester (semester_id),
    KEY idx_periods_framework (framework_id),
    KEY idx_periods_status_end (status, advisor_end_at),
    CONSTRAINT fk_periods_semester FOREIGN KEY (semester_id) REFERENCES semesters (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_periods_framework FOREIGN KEY (framework_id) REFERENCES criteria_frameworks (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_periods_creator FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Đợt đánh giá và các mốc thời gian';

-- ========================================================
-- NHÓM 4: BÀI ĐÁNH GIÁ, ĐIỂM, MINH CHỨNG, DUYỆT, KHIẾU NẠI
-- ========================================================

-- 11. evaluations : bài đánh giá của 1 SV trong 1 đợt (mỗi SV 1 bài / đợt)
--     Luồng status: draft -> submitted -> class_graded -> advisor_approved -> final
--                   (GVCN yêu cầu sửa: class_graded -> need_edit -> submitted ...)
--     Thống kê chỉ nên tính bài có status IN ('advisor_approved','final'):
--     nếu nhóm không làm bước duyệt cuối cấp trường thì 'advisor_approved' là trạng thái cuối.
--     *_total là tổng đã tính sẵn (PHP cập nhật mỗi lần lưu điểm) để thống kê nhanh.
--     final_total = điểm cuối cùng hiện hành: gán bằng điểm GVCN khi duyệt, trường có thể điều chỉnh.
--     rating = xếp loại theo final_total (ngưỡng ở config/constants.php RATINGS), lưu lại để thống kê.
CREATE TABLE evaluations (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    period_id           INT UNSIGNED NOT NULL,
    student_id          INT UNSIGNED NOT NULL,
    status              ENUM('draft','submitted','class_graded','need_edit','advisor_approved','final')
                        NOT NULL DEFAULT 'draft',
    self_total          DECIMAL(5,2) NULL COMMENT 'Tổng điểm SV tự chấm',
    class_total         DECIMAL(5,2) NULL COMMENT 'Tổng điểm lớp chấm',
    advisor_total       DECIMAL(5,2) NULL COMMENT 'Tổng điểm GVCN duyệt',
    final_total         DECIMAL(5,2) NULL COMMENT 'Điểm cuối cùng (nhà trường)',
    rating              VARCHAR(20)  NULL COMMENT 'Xuất sắc | Tốt | Khá | Trung bình | Yếu | Kém',
    self_note           TEXT         NULL COMMENT 'Ghi chú của sinh viên',
    class_note          TEXT         NULL COMMENT 'Ghi chú của lớp',
    advisor_note        TEXT         NULL COMMENT 'Ghi chú của GVCN',
    final_note          TEXT         NULL COMMENT 'Ghi chú của nhà trường',
    submitted_at        DATETIME     NULL,
    class_graded_at     DATETIME     NULL,
    class_graded_by     INT UNSIGNED NULL COMMENT 'Cán bộ lớp (users) đã chấm',
    advisor_approved_at DATETIME     NULL,
    advisor_approved_by INT UNSIGNED NULL,
    finalized_at        DATETIME     NULL,
    finalized_by        INT UNSIGNED NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evaluations_period_student (period_id, student_id),
    KEY idx_evaluations_student (student_id),
    KEY idx_evaluations_status (period_id, status),
    KEY idx_evaluations_rating (rating),
    CONSTRAINT fk_evaluations_period FOREIGN KEY (period_id) REFERENCES evaluation_periods (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluations_student FOREIGN KEY (student_id) REFERENCES students (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evaluations_class_by FOREIGN KEY (class_graded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_evaluations_advisor_by FOREIGN KEY (advisor_approved_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_evaluations_final_by FOREIGN KEY (finalized_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bài đánh giá rèn luyện của sinh viên trong một đợt';

-- 12. evaluation_scores : điểm từng tiêu chí ở 4 cấp (mỗi bài x mỗi tiêu chí = 1 dòng)
CREATE TABLE evaluation_scores (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluation_id INT UNSIGNED NOT NULL,
    item_id       INT UNSIGNED NOT NULL,
    self_score    DECIMAL(5,2) NULL COMMENT 'SV tự chấm',
    class_score   DECIMAL(5,2) NULL COMMENT 'Lớp chấm',
    advisor_score DECIMAL(5,2) NULL COMMENT 'GVCN duyệt',
    final_score   DECIMAL(5,2) NULL COMMENT 'Điểm cuối cùng',
    self_note     VARCHAR(500) NULL COMMENT 'SV giải trình',
    class_note    VARCHAR(500) NULL,
    advisor_note  VARCHAR(500) NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_scores_eval_item (evaluation_id, item_id),
    KEY idx_scores_item (item_id),
    CONSTRAINT fk_scores_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_scores_item FOREIGN KEY (item_id) REFERENCES criteria_items (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Điểm từng tiêu chí: tự chấm, lớp, GVCN, cuối cùng';

-- 13. evidences : file minh chứng (PDF/ảnh) gắn với 1 tiêu chí của 1 bài đánh giá.
--     File thật nằm ở storage/uploads/evidences/<stored_name>, chỉ tải qua api/evidences.php.
--     (Gắn theo evaluation_id + item_id thay vì score_id để upload được ngay cả khi
--      chưa có dòng điểm; vẫn nối được với evaluation_scores qua cặp này.)
CREATE TABLE evidences (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluation_id INT UNSIGNED NOT NULL,
    item_id       INT UNSIGNED NOT NULL,
    original_name VARCHAR(255) NOT NULL COMMENT 'Tên file gốc người dùng tải lên',
    stored_name   VARCHAR(255) NOT NULL COMMENT 'Tên file đã đổi an toàn trên đĩa',
    file_ext      VARCHAR(10)  NOT NULL COMMENT 'pdf, jpg, jpeg, png',
    mime_type     VARCHAR(100) NOT NULL,
    file_size     INT UNSIGNED NOT NULL COMMENT 'Byte',
    description   VARCHAR(255) NULL,
    uploaded_by   INT UNSIGNED NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_evidences_stored (stored_name),
    KEY idx_evidences_eval_item (evaluation_id, item_id),
    KEY idx_evidences_item (item_id),
    CONSTRAINT fk_evidences_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_evidences_item FOREIGN KEY (item_id) REFERENCES criteria_items (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_evidences_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='File minh chứng';

-- 14. approvals : lịch sử chuyển bài giữa các cấp (chỉ thêm, không sửa/xóa)
--     level  : class = lớp, advisor = GVCN, school = nhà trường
--     action : submit = gửi lên cấp trên, approve = duyệt, request_edit = yêu cầu chỉnh sửa
--     Yêu cầu chỉnh sửa BẮT BUỘC có lý do (US-06) - PHP kiểm tra.
CREATE TABLE approvals (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluation_id INT UNSIGNED NOT NULL,
    level         ENUM('class','advisor','school') NOT NULL,
    action        ENUM('submit','approve','request_edit') NOT NULL,
    reason        TEXT         NULL,
    actor_id      INT UNSIGNED NULL COMMENT 'Người thực hiện (users)',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_approvals_eval (evaluation_id, created_at),
    KEY idx_approvals_actor (actor_id),
    CONSTRAINT fk_approvals_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_approvals_actor FOREIGN KEY (actor_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lịch sử duyệt / yêu cầu chỉnh sửa';

-- 15. complaints : khiếu nại / phúc khảo (US-04)
--     item_id NULL = khiếu nại chung về cả bài. Khi đã xử lý (resolved/rejected) phải có phản hồi.
CREATE TABLE complaints (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    evaluation_id INT UNSIGNED NOT NULL,
    item_id       INT UNSIGNED NULL COMMENT 'Tiêu chí bị khiếu nại (NULL = cả bài)',
    content       TEXT         NOT NULL,
    status        ENUM('pending','resolved','rejected') NOT NULL DEFAULT 'pending',
    response      TEXT         NULL COMMENT 'Phản hồi của người xử lý',
    handled_by    INT UNSIGNED NULL,
    handled_at    DATETIME     NULL,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_complaints_eval (evaluation_id),
    KEY idx_complaints_status (status, created_at),
    KEY idx_complaints_item (item_id),
    CONSTRAINT fk_complaints_evaluation FOREIGN KEY (evaluation_id) REFERENCES evaluations (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_complaints_item FOREIGN KEY (item_id) REFERENCES criteria_items (id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_complaints_handler FOREIGN KEY (handled_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Khiếu nại / phúc khảo điểm rèn luyện';

-- ========================================================
-- NHÓM 5: TUỲ CHỌN / HỖ TRỢ
-- ========================================================

-- 16. class_minutes (tuỳ chọn) : biên bản họp lớp, mỗi lớp 1 biên bản / đợt
CREATE TABLE class_minutes (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    class_id        INT UNSIGNED NOT NULL,
    period_id       INT UNSIGNED NOT NULL,
    meeting_at      DATETIME     NULL COMMENT 'Thời gian họp',
    venue           VARCHAR(200) NULL COMMENT 'Địa điểm',
    chairperson     VARCHAR(100) NULL COMMENT 'Chủ trì',
    secretary       VARCHAR(100) NULL COMMENT 'Thư ký',
    present_count   INT UNSIGNED NULL COMMENT 'Số SV có mặt',
    total_count     INT UNSIGNED NULL COMMENT 'Tổng số SV của lớp',
    content         TEXT         NULL COMMENT 'Nội dung cuộc họp',
    conclusion      TEXT         NULL COMMENT 'Kết luận',
    created_by      INT UNSIGNED NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_minutes_class_period (class_id, period_id),
    KEY idx_minutes_period (period_id),
    CONSTRAINT fk_minutes_class FOREIGN KEY (class_id) REFERENCES classes (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_minutes_period FOREIGN KEY (period_id) REFERENCES evaluation_periods (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_minutes_creator FOREIGN KEY (created_by) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Biên bản họp lớp';

-- 17. activity_logs (tuỳ chọn) : nhật ký thao tác (ai làm gì, khi nào)
CREATE TABLE activity_logs (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NULL,
    action      VARCHAR(50)     NOT NULL COMMENT 'Ví dụ: login, submit_evaluation, lock_period',
    entity_type VARCHAR(50)     NULL     COMMENT 'Ví dụ: evaluation, period, criteria_item',
    entity_id   INT UNSIGNED    NULL,
    description VARCHAR(500)    NULL,
    ip_address  VARCHAR(45)     NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_user (user_id, created_at),
    KEY idx_logs_entity (entity_type, entity_id),
    KEY idx_logs_created (created_at),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Nhật ký thao tác';

SET FOREIGN_KEY_CHECKS = 1;
