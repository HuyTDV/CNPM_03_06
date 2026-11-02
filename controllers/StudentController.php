<?php
// Khu vực sinh viên. Cán bộ lớp cũng là sinh viên nên được vào.
class StudentController extends Controller
{
    // Tổng quan: thông tin sinh viên và danh sách các đợt đánh giá
    public function dashboard()
    {
        $user = $this->requireRole([ROLE_STUDENT, ROLE_MONITOR]);
        Period::lockExpired();
        $student = Student::findByUserId($user['id']);

        $periods = [];
        if ($student) {
            foreach (Period::listForStudent($student['id']) as $p) {
                $p['phase'] = Period::phase($p);
                // Còn sửa/nộp được không? (chưa có bài: xem đợt còn trong hạn nộp không)
                $p['can_edit'] = $p['evaluation_id']
                    ? Assessment::canEdit(['status' => $p['evaluation_status']], $p)
                    : Period::studentWindowOpen($p);
                // Chỉ cho xem điểm chính thức khi GVCN đã duyệt
                $p['show_result'] = in_array($p['evaluation_status'], EVAL_RESULT_VISIBLE, true);
                $periods[] = $p;
            }
        }
        $this->render('student/dashboard', ['pageTitle' => 'Tổng quan sinh viên', 'student' => $student, 'periods' => $periods]);
    }
}
