-- ========================================================
-- FILE      : database/seed.sql
-- MÔ TẢ     : Dữ liệu mẫu. CHỈ CÓ GIÁ TRỊ CỐ ĐỊNH, không có phép tính hay hàm SQL.
--             Tổng điểm và xếp loại bên dưới đã được tính sẵn, đúng bằng kết quả
--             mà code PHP (helpers/score.php) sẽ tính.
-- CÁCH CHẠY : Chạy SAU database/schema.sql và chỉ MỘT lần trên CSDL vừa tạo.
--             Muốn nạp lại: chạy lại schema.sql (xóa sạch) rồi seed.sql.
-- --------------------------------------------------------
-- TÀI KHOẢN THỬ (mật khẩu tất cả: 123456 - ĐỔI trước khi triển khai thật)
--   admin     Quản trị viên (Phòng Công tác sinh viên)
--   gv001     GVCN lớp CNTT64A
--   gv002     GVCN lớp CNTT64B và KT65A
--   22110001  Cán bộ lớp CNTT64A (vai trò monitor)
--   22110002  Sinh viên CNTT64A (vai trò student)
--   (còn lại: 22110003..22110006, 22110101..22110103, 23120001..23120003)
-- --------------------------------------------------------
-- DỮ LIỆU ĐỂ THỬ CÁC LUỒNG:
--   Đợt 1 (HK2 2025-2026): đã khóa, 12 SV đều 'final' -> thử lịch sử, thống kê, khiếu nại.
--   Đợt 2 (HK1 2026-2027): đang mở đến 31/12/2026, 5 bài ở các trạng thái khác nhau:
--       22110001, 22110002 = submitted | 22110003 = class_graded
--       22110004 = need_edit (có lý do)  | 22110005 = draft | 22110006 = chưa có bài
--   !! Các mốc của đợt 2 là ngày cố định. Sau 31/12/2026 đợt này sẽ hết hạn:
--      vào trang "Đợt đánh giá" của admin để gia hạn, hoặc sửa 4 mốc ở bảng
--      evaluation_periods bên dưới rồi import lại.
-- LƯU Ý     :
--   - Danh mục tiêu chí mô phỏng mẫu 5 điều (tổng 100 điểm) của Bộ GD&ĐT.
--     PHẢI đối chiếu và sửa lại theo biểu mẫu chính thức của trường.
--   - Chưa có minh chứng mẫu vì file thật phải tải lên qua giao diện.
-- ========================================================

USE ql_ren_luyen;
SET NAMES utf8mb4;

-- 1. roles
INSERT INTO roles (id, code, name, description) VALUES
(1, 'student', 'Sinh viên', 'Tự đánh giá, xem kết quả, gửi khiếu nại'),
(2, 'monitor', 'Cán bộ lớp', 'Kiểm tra minh chứng, chấm điểm cấp lớp (đồng thời là sinh viên)'),
(3, 'advisor', 'Giáo viên chủ nhiệm', 'Duyệt hoặc yêu cầu chỉnh sửa điểm của lớp phụ trách'),
(4, 'admin', 'Quản trị viên', 'Phòng Công tác sinh viên: tiêu chí, đợt đánh giá, thống kê, báo cáo');

-- 2. faculties
INSERT INTO faculties (id, code, name) VALUES
(1, 'CNTT', 'Khoa Công nghệ thông tin'),
(2, 'KT', 'Khoa Kinh tế'),
(3, 'NN', 'Khoa Ngoại ngữ');

-- 3. semesters
INSERT INTO semesters (id, academic_year, term, name, start_date, end_date, is_current) VALUES
(1, '2025-2026', 2, 'Học kỳ 2 năm học 2025-2026', '2026-01-19', '2026-06-14', 0),
(2, '2026-2027', 1, 'Học kỳ 1 năm học 2026-2027', '2026-09-07', '2027-01-17', 1);

-- 4. users (mật khẩu: 123456)
INSERT INTO users (id, username, email, password_hash, full_name, phone, role_id, status, must_change_password, created_by) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Phòng Công tác sinh viên', '0238 000 0001', 4, 'active', 0, NULL),
(2, 'gv001', 'gv001@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Nguyễn Văn Hùng', '0912 000 001', 3, 'active', 0, 1),
(3, 'gv002', 'gv002@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Trần Thị Lan', '0912 000 002', 3, 'active', 0, 1),
(4, '22110001', '22110001@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Nguyễn Minh Anh', '0901 100 001', 2, 'active', 0, 1),
(5, '22110002', '22110002@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Trần Quốc Bảo', '0901 100 002', 1, 'active', 0, 1),
(6, '22110003', '22110003@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Lê Thị Cẩm Linh', '0901 100 003', 1, 'active', 0, 1),
(7, '22110004', '22110004@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Phạm Đức Dũng', '0901 100 004', 1, 'active', 0, 1),
(8, '22110005', '22110005@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Hoàng Thị Hà', '0901 100 005', 1, 'active', 0, 1),
(9, '22110006', '22110006@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Vũ Anh Khoa', '0901 100 006', 1, 'active', 0, 1),
(10, '22110101', '22110101@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Đặng Thu Hương', '0901 100 101', 2, 'active', 0, 1),
(11, '22110102', '22110102@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Bùi Văn Long', '0901 100 102', 1, 'active', 0, 1),
(12, '22110103', '22110103@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Ngô Thị Mai', '0901 100 103', 1, 'active', 0, 1),
(13, '23120001', '23120001@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Đỗ Hải Nam', '0901 200 001', 2, 'active', 0, 1),
(14, '23120002', '23120002@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Lý Thanh Phương', '0901 200 002', 1, 'active', 0, 1),
(15, '23120003', '23120003@example.com', '$2y$10$fIGROns1n7kspBULGWQWw.vamUgYogp3lyMiPvPi3gjiJ4d0IyH5S', 'Trịnh Quang Huy', '0901 200 003', 1, 'active', 0, 1);

-- 5. classes (cán bộ lớp gán ở bước 6 sau khi có students)
INSERT INTO classes (id, code, name, faculty_id, cohort, advisor_id, monitor_id) VALUES
(1, 'CNTT64A', 'Công nghệ thông tin K64 - Lớp A', 1, 'K64', 2, NULL),
(2, 'CNTT64B', 'Công nghệ thông tin K64 - Lớp B', 1, 'K64', 3, NULL),
(3, 'KT65A', 'Kinh tế K65 - Lớp A', 2, 'K65', 3, NULL);

-- 6. students
INSERT INTO students (id, user_id, student_code, class_id, date_of_birth, gender, status) VALUES
(1, 4, '22110001', 1, '2004-03-12', 'female', 'studying'),
(2, 5, '22110002', 1, '2004-07-25', 'male', 'studying'),
(3, 6, '22110003', 1, '2004-11-02', 'female', 'studying'),
(4, 7, '22110004', 1, '2004-01-30', 'male', 'studying'),
(5, 8, '22110005', 1, '2004-05-18', 'female', 'studying'),
(6, 9, '22110006', 1, '2004-09-09', 'male', 'studying'),
(7, 10, '22110101', 2, '2004-02-14', 'female', 'studying'),
(8, 11, '22110102', 2, '2004-06-21', 'male', 'studying'),
(9, 12, '22110103', 2, '2004-12-05', 'female', 'studying'),
(10, 13, '23120001', 3, '2005-04-08', 'male', 'studying'),
(11, 14, '23120002', 3, '2005-08-17', 'female', 'studying'),
(12, 15, '23120003', 3, '2005-10-29', 'male', 'studying');

-- Gán cán bộ lớp
UPDATE classes SET monitor_id = 1 WHERE id = 1;
UPDATE classes SET monitor_id = 7 WHERE id = 2;
UPDATE classes SET monitor_id = 10 WHERE id = 3;

-- 7. criteria_frameworks
INSERT INTO criteria_frameworks (id, semester_id, name, description, total_max_score, is_active, created_by) VALUES
(1, 1, 'Khung điểm rèn luyện HK2 2025-2026', 'Khung điểm 5 điều, tổng 100 điểm', 100, 1, 1),
(2, 2, 'Khung điểm rèn luyện HK1 2026-2027', 'Sao chép từ khung HK2 2025-2026', 100, 1, 1);

-- 8. criteria_categories (khung 1: id 1-5, khung 2: id 6-10)
INSERT INTO criteria_categories (id, framework_id, code, title, max_score, sort_order) VALUES
(1, 1, '1', 'Đánh giá về ý thức tham gia học tập', 20, 1),
(2, 1, '2', 'Đánh giá về ý thức chấp hành nội quy, quy chế, quy định trong nhà trường', 25, 2),
(3, 1, '3', 'Đánh giá về ý thức tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tội phạm và các tệ nạn xã hội', 20, 3),
(4, 1, '4', 'Đánh giá về ý thức công dân trong quan hệ cộng đồng', 25, 4),
(5, 1, '5', 'Đánh giá về ý thức và kết quả khi tham gia công tác cán bộ lớp, các đoàn thể, tổ chức trong nhà trường hoặc đạt thành tích đặc biệt', 10, 5),
(6, 2, '1', 'Đánh giá về ý thức tham gia học tập', 20, 1),
(7, 2, '2', 'Đánh giá về ý thức chấp hành nội quy, quy chế, quy định trong nhà trường', 25, 2),
(8, 2, '3', 'Đánh giá về ý thức tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao, phòng chống tội phạm và các tệ nạn xã hội', 20, 3),
(9, 2, '4', 'Đánh giá về ý thức công dân trong quan hệ cộng đồng', 25, 4),
(10, 2, '5', 'Đánh giá về ý thức và kết quả khi tham gia công tác cán bộ lớp, các đoàn thể, tổ chức trong nhà trường hoặc đạt thành tích đặc biệt', 10, 5);

-- 9. criteria_items (khung 1: id 1-15, khung 2: id 16-30)
INSERT INTO criteria_items (id, category_id, code, content, max_score, min_score, require_evidence, sort_order) VALUES
(1, 1, '1.1', 'Kết quả học tập trong học kỳ (theo điểm trung bình học kỳ)', 10, 0, 1, 1),
(2, 1, '1.2', 'Ý thức và thái độ trong học tập, tham gia đầy đủ các buổi học', 4, 0, 0, 2),
(3, 1, '1.3', 'Tham gia nghiên cứu khoa học, cuộc thi học thuật, câu lạc bộ học thuật', 4, 0, 1, 3),
(4, 1, '1.4', 'Tinh thần vượt khó, phấn đấu vươn lên trong học tập', 2, 0, 0, 4),
(5, 2, '2.1', 'Chấp hành nội quy, quy chế của nhà trường (không vi phạm)', 15, 0, 0, 1),
(6, 2, '2.2', 'Tham gia đầy đủ các buổi sinh hoạt lớp, họp lớp, tuần sinh hoạt công dân', 6, 0, 0, 2),
(7, 2, '2.3', 'Thực hiện đầy đủ thủ tục hành chính (học phí, đăng ký học phần, cập nhật hồ sơ)', 4, 0, 0, 3),
(8, 3, '3.1', 'Tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao do lớp, khoa, trường tổ chức', 10, 0, 1, 1),
(9, 3, '3.2', 'Tham gia hoạt động tình nguyện, công tác xã hội', 6, 0, 1, 2),
(10, 3, '3.3', 'Tham gia phòng chống tội phạm và các tệ nạn xã hội', 4, 0, 0, 3),
(11, 4, '4.1', 'Chấp hành chủ trương, chính sách, pháp luật của Nhà nước', 10, 0, 0, 1),
(12, 4, '4.2', 'Tinh thần giúp đỡ bạn bè, tham gia hoạt động cộng đồng nơi cư trú', 8, 0, 0, 2),
(13, 4, '4.3', 'Có thành tích được biểu dương, khen thưởng về ý thức công dân', 7, 0, 1, 3),
(14, 5, '5.1', 'Là cán bộ lớp, cán bộ Đoàn - Hội và hoàn thành tốt nhiệm vụ được giao', 6, 0, 0, 1),
(15, 5, '5.2', 'Đạt thành tích đặc biệt (giải thưởng, khen thưởng cấp trường trở lên)', 4, 0, 1, 2),
(16, 6, '1.1', 'Kết quả học tập trong học kỳ (theo điểm trung bình học kỳ)', 10, 0, 1, 1),
(17, 6, '1.2', 'Ý thức và thái độ trong học tập, tham gia đầy đủ các buổi học', 4, 0, 0, 2),
(18, 6, '1.3', 'Tham gia nghiên cứu khoa học, cuộc thi học thuật, câu lạc bộ học thuật', 4, 0, 1, 3),
(19, 6, '1.4', 'Tinh thần vượt khó, phấn đấu vươn lên trong học tập', 2, 0, 0, 4),
(20, 7, '2.1', 'Chấp hành nội quy, quy chế của nhà trường (không vi phạm)', 15, 0, 0, 1),
(21, 7, '2.2', 'Tham gia đầy đủ các buổi sinh hoạt lớp, họp lớp, tuần sinh hoạt công dân', 6, 0, 0, 2),
(22, 7, '2.3', 'Thực hiện đầy đủ thủ tục hành chính (học phí, đăng ký học phần, cập nhật hồ sơ)', 4, 0, 0, 3),
(23, 8, '3.1', 'Tham gia các hoạt động chính trị, xã hội, văn hóa, văn nghệ, thể thao do lớp, khoa, trường tổ chức', 10, 0, 1, 1),
(24, 8, '3.2', 'Tham gia hoạt động tình nguyện, công tác xã hội', 6, 0, 1, 2),
(25, 8, '3.3', 'Tham gia phòng chống tội phạm và các tệ nạn xã hội', 4, 0, 0, 3),
(26, 9, '4.1', 'Chấp hành chủ trương, chính sách, pháp luật của Nhà nước', 10, 0, 0, 1),
(27, 9, '4.2', 'Tinh thần giúp đỡ bạn bè, tham gia hoạt động cộng đồng nơi cư trú', 8, 0, 0, 2),
(28, 9, '4.3', 'Có thành tích được biểu dương, khen thưởng về ý thức công dân', 7, 0, 1, 3),
(29, 10, '5.1', 'Là cán bộ lớp, cán bộ Đoàn - Hội và hoàn thành tốt nhiệm vụ được giao', 6, 0, 0, 1),
(30, 10, '5.2', 'Đạt thành tích đặc biệt (giải thưởng, khen thưởng cấp trường trở lên)', 4, 0, 1, 2);

-- 10. evaluation_periods
INSERT INTO evaluation_periods (id, name, semester_id, framework_id, start_at, student_end_at, class_end_at, advisor_end_at, status, locked_at, created_by) VALUES
(1, 'Đánh giá rèn luyện HK2 2025-2026', 1, 1, '2026-05-18 08:00:00', '2026-06-01 23:59:59', '2026-06-08 23:59:59', '2026-06-15 23:59:59', 'locked', '2026-06-16 00:05:00', 1),
(2, 'Đánh giá rèn luyện HK1 2026-2027', 2, 2, '2026-10-26 08:00:00', '2026-12-15 23:59:59', '2026-12-22 23:59:59', '2026-12-31 23:59:59', 'open', NULL, 1);

-- 11. evaluations (id 1-12: đợt 1 đã final; id 13-17: đợt 2)
INSERT INTO evaluations (id, period_id, student_id, status, self_total, class_total, advisor_total, final_total, rating, self_note, class_note, advisor_note, final_note, submitted_at, class_graded_at, class_graded_by, advisor_approved_at, advisor_approved_by, finalized_at, finalized_by) VALUES
(1, 1, 1, 'final', 100, 95.9, 95.9, 95.9, 'Xuất sắc', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(2, 1, 2, 'final', 92.0, 88.0, 88.0, 88.0, 'Tốt', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(3, 1, 3, 'final', 85.9, 82.0, 82.0, 82.0, 'Tốt', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(4, 1, 4, 'final', 78.0, 74.1, 74.1, 74.1, 'Khá', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(5, 1, 5, 'final', 73.0, 69.1, 69.1, 69.1, 'Khá', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(6, 1, 6, 'final', 45.9, 42.0, 42.0, 42.0, 'Yếu', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 4, '2026-06-10 15:00:00', 2, '2026-06-14 09:00:00', 1),
(7, 1, 7, 'final', 95.1, 91.0, 91.0, 91.0, 'Xuất sắc', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 10, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(8, 1, 8, 'final', 87.1, 83.0, 83.0, 83.0, 'Tốt', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 10, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(9, 1, 9, 'final', 71.0, 67.1, 67.1, 67.1, 'Khá', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 10, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(10, 1, 10, 'final', 81.0, 77.1, 77.1, 77.1, 'Khá', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 13, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(11, 1, 11, 'final', 59.1, 55.1, 55.1, 55.1, 'Trung bình', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 13, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(12, 1, 12, 'final', 97.1, 93.0, 93.0, 93.0, 'Xuất sắc', 'Em tự đánh giá theo đúng minh chứng đã nộp.', 'Lớp thống nhất điểm tại buổi họp lớp.', 'GVCN đã kiểm tra minh chứng.', 'Nhà trường xác nhận kết quả.', '2026-05-25 10:00:00', '2026-06-03 20:30:00', 13, '2026-06-10 15:00:00', 3, '2026-06-14 09:00:00', 1),
(13, 2, 1, 'submitted', 93.0, NULL, NULL, NULL, NULL, 'Em đã đính kèm đầy đủ minh chứng.', NULL, NULL, NULL, '2026-10-29 09:15:00', NULL, NULL, NULL, NULL, NULL, NULL),
(14, 2, 2, 'submitted', 85.9, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-10-28 20:40:00', NULL, NULL, NULL, NULL, NULL, NULL),
(15, 2, 3, 'class_graded', 80.0, 77.1, NULL, NULL, NULL, NULL, 'Lớp đã kiểm tra minh chứng và thống nhất điểm.', NULL, NULL, '2026-10-28 21:00:00', '2026-10-29 19:30:00', 4, NULL, NULL, NULL, NULL),
(16, 2, 4, 'need_edit', 72.0, 69.1, NULL, NULL, NULL, NULL, 'Lớp đã chấm, chuyển GVCN.', 'Mục 3.1 chưa có minh chứng đính kèm, em bổ sung rồi gửi lại.', NULL, '2026-10-28 22:10:00', '2026-10-29 19:45:00', 4, NULL, NULL, NULL, NULL),
(17, 2, 5, 'draft', 29.3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- 12. evaluation_scores
INSERT INTO evaluation_scores (evaluation_id, item_id, self_score, class_score, advisor_score, final_score) VALUES
(1, 1, 10, 9.6, 9.6, 9.6),
(1, 2, 4, 3.8, 3.8, 3.8),
(1, 3, 4, 3.8, 3.8, 3.8),
(1, 4, 2, 1.9, 1.9, 1.9),
(1, 5, 15, 14.4, 14.4, 14.4),
(1, 6, 6, 5.8, 5.8, 5.8),
(1, 7, 4, 3.8, 3.8, 3.8),
(1, 8, 10, 9.6, 9.6, 9.6),
(1, 9, 6, 5.8, 5.8, 5.8),
(1, 10, 4, 3.8, 3.8, 3.8),
(1, 11, 10, 9.6, 9.6, 9.6),
(1, 12, 8, 7.7, 7.7, 7.7),
(1, 13, 7, 6.7, 6.7, 6.7),
(1, 14, 6, 5.8, 5.8, 5.8),
(1, 15, 4, 3.8, 3.8, 3.8),
(2, 1, 9.2, 8.8, 8.8, 8.8),
(2, 2, 3.7, 3.5, 3.5, 3.5),
(2, 3, 3.7, 3.5, 3.5, 3.5),
(2, 4, 1.8, 1.8, 1.8, 1.8),
(2, 5, 13.8, 13.2, 13.2, 13.2),
(2, 6, 5.5, 5.3, 5.3, 5.3),
(2, 7, 3.7, 3.5, 3.5, 3.5),
(2, 8, 9.2, 8.8, 8.8, 8.8),
(2, 9, 5.5, 5.3, 5.3, 5.3),
(2, 10, 3.7, 3.5, 3.5, 3.5),
(2, 11, 9.2, 8.8, 8.8, 8.8),
(2, 12, 7.4, 7.0, 7.0, 7.0),
(2, 13, 6.4, 6.2, 6.2, 6.2),
(2, 14, 5.5, 5.3, 5.3, 5.3),
(2, 15, 3.7, 3.5, 3.5, 3.5),
(3, 1, 8.6, 8.2, 8.2, 8.2),
(3, 2, 3.4, 3.3, 3.3, 3.3),
(3, 3, 3.4, 3.3, 3.3, 3.3),
(3, 4, 1.7, 1.6, 1.6, 1.6),
(3, 5, 12.9, 12.3, 12.3, 12.3),
(3, 6, 5.2, 4.9, 4.9, 4.9),
(3, 7, 3.4, 3.3, 3.3, 3.3),
(3, 8, 8.6, 8.2, 8.2, 8.2),
(3, 9, 5.2, 4.9, 4.9, 4.9),
(3, 10, 3.4, 3.3, 3.3, 3.3),
(3, 11, 8.6, 8.2, 8.2, 8.2),
(3, 12, 6.9, 6.6, 6.6, 6.6),
(3, 13, 6.0, 5.7, 5.7, 5.7),
(3, 14, 5.2, 4.9, 4.9, 4.9),
(3, 15, 3.4, 3.3, 3.3, 3.3),
(4, 1, 7.8, 7.4, 7.4, 7.4),
(4, 2, 3.1, 3.0, 3.0, 3.0),
(4, 3, 3.1, 3.0, 3.0, 3.0),
(4, 4, 1.6, 1.5, 1.5, 1.5),
(4, 5, 11.7, 11.1, 11.1, 11.1),
(4, 6, 4.7, 4.4, 4.4, 4.4),
(4, 7, 3.1, 3.0, 3.0, 3.0),
(4, 8, 7.8, 7.4, 7.4, 7.4),
(4, 9, 4.7, 4.4, 4.4, 4.4),
(4, 10, 3.1, 3.0, 3.0, 3.0),
(4, 11, 7.8, 7.4, 7.4, 7.4),
(4, 12, 6.2, 5.9, 5.9, 5.9),
(4, 13, 5.5, 5.2, 5.2, 5.2),
(4, 14, 4.7, 4.4, 4.4, 4.4),
(4, 15, 3.1, 3.0, 3.0, 3.0),
(5, 1, 7.3, 6.9, 6.9, 6.9),
(5, 2, 2.9, 2.8, 2.8, 2.8),
(5, 3, 2.9, 2.8, 2.8, 2.8),
(5, 4, 1.5, 1.4, 1.4, 1.4),
(5, 5, 11.0, 10.4, 10.4, 10.4),
(5, 6, 4.4, 4.1, 4.1, 4.1),
(5, 7, 2.9, 2.8, 2.8, 2.8),
(5, 8, 7.3, 6.9, 6.9, 6.9),
(5, 9, 4.4, 4.1, 4.1, 4.1),
(5, 10, 2.9, 2.8, 2.8, 2.8),
(5, 11, 7.3, 6.9, 6.9, 6.9),
(5, 12, 5.8, 5.5, 5.5, 5.5),
(5, 13, 5.1, 4.8, 4.8, 4.8),
(5, 14, 4.4, 4.1, 4.1, 4.1),
(5, 15, 2.9, 2.8, 2.8, 2.8),
(6, 1, 4.6, 4.2, 4.2, 4.2),
(6, 2, 1.8, 1.7, 1.7, 1.7),
(6, 3, 1.8, 1.7, 1.7, 1.7),
(6, 4, 0.9, 0.8, 0.8, 0.8),
(6, 5, 6.9, 6.3, 6.3, 6.3),
(6, 6, 2.8, 2.5, 2.5, 2.5),
(6, 7, 1.8, 1.7, 1.7, 1.7),
(6, 8, 4.6, 4.2, 4.2, 4.2),
(6, 9, 2.8, 2.5, 2.5, 2.5),
(6, 10, 1.8, 1.7, 1.7, 1.7),
(6, 11, 4.6, 4.2, 4.2, 4.2),
(6, 12, 3.7, 3.4, 3.4, 3.4),
(6, 13, 3.2, 2.9, 2.9, 2.9),
(6, 14, 2.8, 2.5, 2.5, 2.5),
(6, 15, 1.8, 1.7, 1.7, 1.7),
(7, 1, 9.5, 9.1, 9.1, 9.1),
(7, 2, 3.8, 3.6, 3.6, 3.6),
(7, 3, 3.8, 3.6, 3.6, 3.6),
(7, 4, 1.9, 1.8, 1.8, 1.8),
(7, 5, 14.3, 13.7, 13.7, 13.7),
(7, 6, 5.7, 5.5, 5.5, 5.5),
(7, 7, 3.8, 3.6, 3.6, 3.6),
(7, 8, 9.5, 9.1, 9.1, 9.1),
(7, 9, 5.7, 5.5, 5.5, 5.5),
(7, 10, 3.8, 3.6, 3.6, 3.6),
(7, 11, 9.5, 9.1, 9.1, 9.1),
(7, 12, 7.6, 7.3, 7.3, 7.3),
(7, 13, 6.7, 6.4, 6.4, 6.4),
(7, 14, 5.7, 5.5, 5.5, 5.5),
(7, 15, 3.8, 3.6, 3.6, 3.6),
(8, 1, 8.7, 8.3, 8.3, 8.3),
(8, 2, 3.5, 3.3, 3.3, 3.3),
(8, 3, 3.5, 3.3, 3.3, 3.3),
(8, 4, 1.7, 1.7, 1.7, 1.7),
(8, 5, 13.1, 12.5, 12.5, 12.5),
(8, 6, 5.2, 5.0, 5.0, 5.0),
(8, 7, 3.5, 3.3, 3.3, 3.3),
(8, 8, 8.7, 8.3, 8.3, 8.3),
(8, 9, 5.2, 5.0, 5.0, 5.0),
(8, 10, 3.5, 3.3, 3.3, 3.3),
(8, 11, 8.7, 8.3, 8.3, 8.3),
(8, 12, 7.0, 6.6, 6.6, 6.6),
(8, 13, 6.1, 5.8, 5.8, 5.8),
(8, 14, 5.2, 5.0, 5.0, 5.0),
(8, 15, 3.5, 3.3, 3.3, 3.3),
(9, 1, 7.1, 6.7, 6.7, 6.7),
(9, 2, 2.8, 2.7, 2.7, 2.7),
(9, 3, 2.8, 2.7, 2.7, 2.7),
(9, 4, 1.4, 1.3, 1.3, 1.3),
(9, 5, 10.7, 10.1, 10.1, 10.1),
(9, 6, 4.3, 4.0, 4.0, 4.0),
(9, 7, 2.8, 2.7, 2.7, 2.7),
(9, 8, 7.1, 6.7, 6.7, 6.7),
(9, 9, 4.3, 4.0, 4.0, 4.0),
(9, 10, 2.8, 2.7, 2.7, 2.7),
(9, 11, 7.1, 6.7, 6.7, 6.7),
(9, 12, 5.7, 5.4, 5.4, 5.4),
(9, 13, 5.0, 4.7, 4.7, 4.7),
(9, 14, 4.3, 4.0, 4.0, 4.0),
(9, 15, 2.8, 2.7, 2.7, 2.7),
(10, 1, 8.1, 7.7, 7.7, 7.7),
(10, 2, 3.2, 3.1, 3.1, 3.1),
(10, 3, 3.2, 3.1, 3.1, 3.1),
(10, 4, 1.6, 1.5, 1.5, 1.5),
(10, 5, 12.2, 11.6, 11.6, 11.6),
(10, 6, 4.9, 4.6, 4.6, 4.6),
(10, 7, 3.2, 3.1, 3.1, 3.1),
(10, 8, 8.1, 7.7, 7.7, 7.7),
(10, 9, 4.9, 4.6, 4.6, 4.6),
(10, 10, 3.2, 3.1, 3.1, 3.1),
(10, 11, 8.1, 7.7, 7.7, 7.7),
(10, 12, 6.5, 6.2, 6.2, 6.2),
(10, 13, 5.7, 5.4, 5.4, 5.4),
(10, 14, 4.9, 4.6, 4.6, 4.6),
(10, 15, 3.2, 3.1, 3.1, 3.1),
(11, 1, 5.9, 5.5, 5.5, 5.5),
(11, 2, 2.4, 2.2, 2.2, 2.2),
(11, 3, 2.4, 2.2, 2.2, 2.2),
(11, 4, 1.2, 1.1, 1.1, 1.1),
(11, 5, 8.9, 8.3, 8.3, 8.3),
(11, 6, 3.5, 3.3, 3.3, 3.3),
(11, 7, 2.4, 2.2, 2.2, 2.2),
(11, 8, 5.9, 5.5, 5.5, 5.5),
(11, 9, 3.5, 3.3, 3.3, 3.3),
(11, 10, 2.4, 2.2, 2.2, 2.2),
(11, 11, 5.9, 5.5, 5.5, 5.5),
(11, 12, 4.7, 4.4, 4.4, 4.4),
(11, 13, 4.1, 3.9, 3.9, 3.9),
(11, 14, 3.5, 3.3, 3.3, 3.3),
(11, 15, 2.4, 2.2, 2.2, 2.2),
(12, 1, 9.7, 9.3, 9.3, 9.3),
(12, 2, 3.9, 3.7, 3.7, 3.7),
(12, 3, 3.9, 3.7, 3.7, 3.7),
(12, 4, 1.9, 1.9, 1.9, 1.9),
(12, 5, 14.6, 14.0, 14.0, 14.0),
(12, 6, 5.8, 5.6, 5.6, 5.6),
(12, 7, 3.9, 3.7, 3.7, 3.7),
(12, 8, 9.7, 9.3, 9.3, 9.3),
(12, 9, 5.8, 5.6, 5.6, 5.6),
(12, 10, 3.9, 3.7, 3.7, 3.7),
(12, 11, 9.7, 9.3, 9.3, 9.3),
(12, 12, 7.8, 7.4, 7.4, 7.4),
(12, 13, 6.8, 6.5, 6.5, 6.5),
(12, 14, 5.8, 5.6, 5.6, 5.6),
(12, 15, 3.9, 3.7, 3.7, 3.7),
(13, 16, 9.3, NULL, NULL, NULL),
(13, 17, 3.7, NULL, NULL, NULL),
(13, 18, 3.7, NULL, NULL, NULL),
(13, 19, 1.9, NULL, NULL, NULL),
(13, 20, 14.0, NULL, NULL, NULL),
(13, 21, 5.6, NULL, NULL, NULL),
(13, 22, 3.7, NULL, NULL, NULL),
(13, 23, 9.3, NULL, NULL, NULL),
(13, 24, 5.6, NULL, NULL, NULL),
(13, 25, 3.7, NULL, NULL, NULL),
(13, 26, 9.3, NULL, NULL, NULL),
(13, 27, 7.4, NULL, NULL, NULL),
(13, 28, 6.5, NULL, NULL, NULL),
(13, 29, 5.6, NULL, NULL, NULL),
(13, 30, 3.7, NULL, NULL, NULL),
(14, 16, 8.6, NULL, NULL, NULL),
(14, 17, 3.4, NULL, NULL, NULL),
(14, 18, 3.4, NULL, NULL, NULL),
(14, 19, 1.7, NULL, NULL, NULL),
(14, 20, 12.9, NULL, NULL, NULL),
(14, 21, 5.2, NULL, NULL, NULL),
(14, 22, 3.4, NULL, NULL, NULL),
(14, 23, 8.6, NULL, NULL, NULL),
(14, 24, 5.2, NULL, NULL, NULL),
(14, 25, 3.4, NULL, NULL, NULL),
(14, 26, 8.6, NULL, NULL, NULL),
(14, 27, 6.9, NULL, NULL, NULL),
(14, 28, 6.0, NULL, NULL, NULL),
(14, 29, 5.2, NULL, NULL, NULL),
(14, 30, 3.4, NULL, NULL, NULL),
(15, 16, 8.0, 7.7, NULL, NULL),
(15, 17, 3.2, 3.1, NULL, NULL),
(15, 18, 3.2, 3.1, NULL, NULL),
(15, 19, 1.6, 1.5, NULL, NULL),
(15, 20, 12.0, 11.6, NULL, NULL),
(15, 21, 4.8, 4.6, NULL, NULL),
(15, 22, 3.2, 3.1, NULL, NULL),
(15, 23, 8.0, 7.7, NULL, NULL),
(15, 24, 4.8, 4.6, NULL, NULL),
(15, 25, 3.2, 3.1, NULL, NULL),
(15, 26, 8.0, 7.7, NULL, NULL),
(15, 27, 6.4, 6.2, NULL, NULL),
(15, 28, 5.6, 5.4, NULL, NULL),
(15, 29, 4.8, 4.6, NULL, NULL),
(15, 30, 3.2, 3.1, NULL, NULL),
(16, 16, 7.2, 6.9, NULL, NULL),
(16, 17, 2.9, 2.8, NULL, NULL),
(16, 18, 2.9, 2.8, NULL, NULL),
(16, 19, 1.4, 1.4, NULL, NULL),
(16, 20, 10.8, 10.4, NULL, NULL),
(16, 21, 4.3, 4.1, NULL, NULL),
(16, 22, 2.9, 2.8, NULL, NULL),
(16, 23, 7.2, 6.9, NULL, NULL),
(16, 24, 4.3, 4.1, NULL, NULL),
(16, 25, 2.9, 2.8, NULL, NULL),
(16, 26, 7.2, 6.9, NULL, NULL),
(16, 27, 5.8, 5.5, NULL, NULL),
(16, 28, 5.0, 4.8, NULL, NULL),
(16, 29, 4.3, 4.1, NULL, NULL),
(16, 30, 2.9, 2.8, NULL, NULL),
(17, 16, 6.5, NULL, NULL, NULL),
(17, 17, 2.6, NULL, NULL, NULL),
(17, 18, 2.6, NULL, NULL, NULL),
(17, 19, 1.3, NULL, NULL, NULL),
(17, 20, 9.8, NULL, NULL, NULL),
(17, 21, 3.9, NULL, NULL, NULL),
(17, 22, 2.6, NULL, NULL, NULL);

-- 14. approvals (lịch sử duyệt)
INSERT INTO approvals (evaluation_id, level, action, reason, actor_id, created_at) VALUES
(1, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(1, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(1, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(2, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(2, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(2, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(3, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(3, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(3, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(4, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(4, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(4, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(5, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(5, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(5, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(6, 'class', 'submit', NULL, 4, '2026-06-03 20:30:00'),
(6, 'advisor', 'approve', NULL, 2, '2026-06-10 15:00:00'),
(6, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(7, 'class', 'submit', NULL, 10, '2026-06-03 20:30:00'),
(7, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(7, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(8, 'class', 'submit', NULL, 10, '2026-06-03 20:30:00'),
(8, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(8, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(9, 'class', 'submit', NULL, 10, '2026-06-03 20:30:00'),
(9, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(9, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(10, 'class', 'submit', NULL, 13, '2026-06-03 20:30:00'),
(10, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(10, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(11, 'class', 'submit', NULL, 13, '2026-06-03 20:30:00'),
(11, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(11, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(12, 'class', 'submit', NULL, 13, '2026-06-03 20:30:00'),
(12, 'advisor', 'approve', NULL, 3, '2026-06-10 15:00:00'),
(12, 'school', 'approve', NULL, 1, '2026-06-14 09:00:00'),
(15, 'class', 'submit', NULL, 4, '2026-10-29 19:35:00'),
(16, 'class', 'submit', NULL, 4, '2026-10-29 19:50:00'),
(16, 'advisor', 'request_edit', 'Mục 3.1 (hoạt động chính trị, xã hội, văn hóa, thể thao) thiếu minh chứng. Em bổ sung giấy xác nhận tham gia rồi nộp lại.', 2, '2026-10-30 08:20:00');

-- 15. complaints (đủ 3 trạng thái)
INSERT INTO complaints (evaluation_id, item_id, content, status, response, handled_by, handled_at, created_at) VALUES
(3, 8, 'Em có tham gia hội thao khoa nhưng điểm mục 3.1 thấp hơn so với số hoạt động đã tham gia. Em xin được xem xét lại.', 'pending', NULL, NULL, NULL, '2026-06-12 08:30:00'),
(5, 1, 'Điểm trung bình học kỳ của em là 3.2 nhưng điểm mục 1.1 chưa tương ứng. Xin được kiểm tra lại.', 'resolved', 'Đã đối chiếu bảng điểm học kỳ và xác nhận điểm mục 1.1 là chính xác theo thang quy định.', 1, '2026-06-13 14:00:00', '2026-06-11 09:15:00'),
(6, 5, 'Em đề nghị xem xét lại điểm mục 2.1 vì em chưa từng bị nhắc nhở vi phạm nội quy.', 'rejected', 'Hồ sơ ghi nhận 2 lần vắng mặt không phép ở tuần sinh hoạt công dân; không đủ cơ sở điều chỉnh điểm.', 1, '2026-06-13 16:30:00', '2026-06-11 20:00:00');

-- 16. class_minutes
INSERT INTO class_minutes (class_id, period_id, meeting_at, venue, chairperson, secretary, present_count, total_count, content, conclusion, created_by) VALUES
(1, 1, '2026-06-02 19:00:00', 'Phòng A2-301', 'Nguyễn Minh Anh', 'Hoàng Thị Hà', 6, 6, 'Lớp thông qua bảng tự đánh giá của từng sinh viên, đối chiếu minh chứng và thống nhất điểm đề xuất của lớp.', 'Lớp thống nhất điểm rèn luyện và đề nghị GVCN xem xét, phê duyệt.', 4);

-- 17. activity_logs
INSERT INTO activity_logs (user_id, action, entity_type, entity_id, description, ip_address, created_at) VALUES
(1, 'lock_period', 'evaluation_period', 1, 'Khóa đợt đánh giá HK2 2025-2026', '127.0.0.1', '2026-06-16 00:05:00'),
(1, 'create_period', 'evaluation_period', 2, 'Tạo đợt đánh giá HK1 2026-2027', '127.0.0.1', '2026-10-26 07:50:00'),
(2, 'request_edit', 'evaluation', 16, 'GVCN yêu cầu sinh viên 22110004 chỉnh sửa', '127.0.0.1', '2026-10-30 08:20:00');
