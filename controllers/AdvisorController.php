<?php
// US-06: giáo viên chủ nhiệm duyệt điểm rèn luyện của lớp mình phụ trách.
//
// Quy tắc (kiểm tra bằng PHP):
//  - GVCN chỉ thấy và duyệt các LỚP MÌNH PHỤ TRÁCH.
//  - Chỉ duyệt khi Review::advisorCanReview() cho phép (lớp đã chấm xong, đợt mở, còn hạn GVCN duyệt).
//  - Duyệt từng bạn (nhập điểm GVCN từng khoản) hoặc hàng loạt (lấy điểm lớp chấm làm điểm duyệt).
//  - Yêu cầu chỉnh sửa BẮT BUỘC có lý do; sinh viên sẽ thấy lý do và sửa lại được.
//  - Điểm GVCN duyệt trở thành điểm chính thức; hệ thống tự tính tổng và xếp loại.
class AdvisorController extends Controller
{
    // Tổng quan: mỗi dòng là một lớp trong một đợt, kèm số bài theo trạng thái
    public function dashboard()
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        Period::lockExpired();
        $periods = Period::started();
        $rows = [];
        foreach (Classroom::forAdvisor($user['id']) as $class) {
            foreach ($periods as $p) {
                $p['phase'] = Period::phase($p);
                $rows[] = ['class' => $class, 'period' => $p, 'summary' => Review::summary(Review::students($class['id'], $p['id']))];
            }
        }
        $this->render('advisor/dashboard', ['pageTitle' => 'Khu vực giáo viên chủ nhiệm', 'rows' => $rows]);
    }

    // Menu "Duyệt điểm lớp": chuyển tới lớp đầu tiên, đợt mới nhất
    public function current()
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        $classes = Classroom::forAdvisor($user['id']);
        $period = Period::latest();
        if (!$classes || !$period) {
            flash('info', 'Hiện chưa có lớp phụ trách hoặc chưa có đợt đánh giá nào.');
            $this->redirect('advisor/dashboard');
        }
        $this->redirect('advisor/review/' . $classes[0]['id'] . '/' . $period['id']);
    }

    // Danh sách sinh viên của một lớp trong một đợt
    public function review(int $classId, int $periodId)
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        Period::lockExpired();
        $class = $this->myClass($classId, $user);
        $period = $this->periodOr404($periodId);

        $list = Review::students($classId, $periodId);
        foreach ($list as $k => $r) {
            $list[$k]['is_monitor'] = (int) $r['student_id'] === (int) $class['monitor_id'];
            $list[$k]['can_review'] = $r['evaluation_id'] && Review::advisorCanReview(
                ['status' => $r['status'], 'student_id' => $r['student_id'], 'monitor_id' => $class['monitor_id']], $period);
        }
        $this->render('advisor/review', [
            'pageTitle' => 'Duyệt điểm lớp',
            'class'     => $class,
            'period'    => $period,
            'phase'     => Period::phase($period),
            'list'      => $list,
            'summary'   => Review::summary($list),
            'scripts'   => ['js/review.js'],
        ]);
    }

    // Chi tiết một sinh viên: điểm các cấp, minh chứng, lịch sử; nhập điểm GVCN
    public function student(int $evaluationId)
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        Period::lockExpired();
        $ev = $this->evaluationOfMine($evaluationId, $user);
        $period = Period::find($ev['period_id']);

        $old = $_SESSION['old_review'] ?? [];
        unset($_SESSION['old_review']);
        $this->render('advisor/student', [
            'pageTitle' => 'Duyệt điểm',
            'ev'        => $ev,
            'period'    => $period,
            'canEdit'   => Review::advisorCanReview($ev, $period),
            'tree'      => Criteria::tree($period['framework_id']),
            'scores'    => Assessment::scores($ev['id']),
            'evidences' => Assessment::evidencesByItem($ev['id']),
            'history'   => Approval::history($ev['id']),
            'old'       => $old,
            'scripts'   => ['js/assessment.js'],
        ]);
    }

    // POST: duyệt một sinh viên với điểm GVCN nhập trong form
    public function approve(int $evaluationId)
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        $this->requirePost();
        Period::lockExpired();
        $ev = $this->evaluationOfMine($evaluationId, $user);
        $period = Period::find($ev['period_id']);
        $back = 'advisor/student/' . $evaluationId;

        if (!Review::advisorCanReview($ev, $period)) {
            flash('danger', 'Bạn không thể duyệt bài này lúc này (chưa đến lượt GVCN, đã duyệt, hết hạn hoặc đợt đã khóa).');
            $this->redirect($back);
        }
        $items = tree_items(Criteria::tree($period['framework_id']));
        $rawScores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
        $rawNotes = is_array($_POST['notes'] ?? null) ? $_POST['notes'] : [];
        [$rows, $errors] = read_score_rows($items, $rawScores, $rawNotes, true);
        $note = mb_substr($this->post('advisor_note'), 0, 1000);

        if ($errors) {
            $_SESSION['old_review'] = ['scores' => $rawScores, 'notes' => $rawNotes, 'note' => $note];
            flash('danger', errors_message('Chưa duyệt được, vui lòng sửa các lỗi sau:', $errors));
            $this->redirect($back);
        }
        [$total, $rating] = Review::approve($evaluationId, $rows, $note !== '' ? $note : null, $user['id']);
        flash('success', 'Đã duyệt ' . $ev['full_name'] . ': ' . fmt_score($total) . ' điểm, xếp loại ' . $rating . '.');
        $this->redirect('advisor/review/' . $ev['class_id'] . '/' . $ev['period_id']);
    }

    // POST: yêu cầu sinh viên chỉnh sửa (bắt buộc có lý do)
    public function requestEdit(int $evaluationId)
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        $this->requirePost();
        Period::lockExpired();
        $ev = $this->evaluationOfMine($evaluationId, $user);
        $period = Period::find($ev['period_id']);
        $back = 'advisor/student/' . $evaluationId;

        if (!Review::advisorCanReview($ev, $period)) {
            flash('danger', 'Bạn không thể yêu cầu chỉnh sửa bài này lúc này.');
            $this->redirect($back);
        }
        $reason = $this->post('reason');
        if ($reason === '' || mb_strlen($reason) > 1000) {
            flash('danger', 'Yêu cầu chỉnh sửa bắt buộc phải có lý do (tối đa 1000 ký tự).');
            $this->redirect($back);
        }
        Review::requestEdit($evaluationId, $reason, $user['id']);
        flash('success', 'Đã yêu cầu ' . $ev['full_name'] . ' chỉnh sửa. Sinh viên sẽ thấy lý do và sửa lại bài.');
        $this->redirect('advisor/review/' . $ev['class_id'] . '/' . $ev['period_id']);
    }

    // POST: duyệt hàng loạt các bài đã chọn, lấy điểm lớp chấm làm điểm duyệt
    // (bài của cán bộ lớp hoặc bài lớp chưa chấm thì lấy điểm tự chấm)
    public function approveBulk(int $classId, int $periodId)
    {
        $user = $this->requireRole([ROLE_ADVISOR]);
        $this->requirePost();
        Period::lockExpired();
        $class = $this->myClass($classId, $user);
        $period = $this->periodOr404($periodId);
        $back = 'advisor/review/' . $classId . '/' . $periodId;

        $ids = is_array($_POST['ids'] ?? null) ? $_POST['ids'] : [];
        if (!$ids) {
            flash('warning', 'Bạn chưa chọn sinh viên nào.');
            $this->redirect($back);
        }
        $items = tree_items(Criteria::tree($period['framework_id']));
        $done = 0;
        $skipped = [];
        foreach (array_unique($ids) as $id) {
            $ev = ctype_digit((string) $id) ? Review::evaluation((int) $id) : null;
            if (!$ev || (int) $ev['class_id'] !== $classId || (int) $ev['period_id'] !== $periodId
                || !Review::advisorCanReview($ev, $period)) {
                continue;                                   // không hợp lệ / không thuộc lớp / chưa đến lượt: bỏ qua
            }
            $scores = Assessment::scores($ev['id']);
            $rows = [];
            foreach ($items as $itemId => $it) {
                $s = $scores[$itemId]['class_score'] ?? $scores[$itemId]['self_score'] ?? null;
                if ($s === null) {
                    $skipped[] = $ev['student_code'];       // thiếu điểm ở một khoản: để duyệt riêng
                    continue 2;
                }
                $rows[$itemId] = ['score' => (float) $s, 'note' => null];
            }
            Review::approve($ev['id'], $rows, null, $user['id']);
            $done++;
        }
        $msg = "Đã duyệt $done sinh viên.";
        if ($skipped) {
            $msg .= ' Chưa duyệt (thiếu điểm, hãy duyệt riêng): ' . implode(', ', $skipped) . '.';
        }
        flash($done ? 'success' : 'warning', $done || $skipped ? $msg : 'Không có bài nào duyệt được (chưa đến lượt GVCN, đã duyệt hoặc hết hạn).');
        $this->redirect($back);
    }

    // ---------------------------------------------------------------- Hỗ trợ

    // Lớp phải do GVCN này phụ trách, nếu không là 403
    private function myClass($classId, $user)
    {
        $class = Classroom::find($classId);
        if (!$class) {
            $this->abort(404, 'Không tìm thấy lớp.');
        }
        if ((int) $class['advisor_id'] !== (int) $user['id']) {
            $this->abort(403, 'Bạn không phụ trách lớp này.');
        }
        return $class;
    }

    private function periodOr404($periodId)
    {
        $period = Period::find($periodId);
        if (!$period || now_str() < $period['start_at']) {
            $this->abort(404, 'Không tìm thấy đợt đánh giá.');
        }
        return $period;
    }

    // Bài đánh giá phải thuộc lớp do GVCN này phụ trách
    private function evaluationOfMine($evaluationId, $user)
    {
        $ev = Review::evaluation($evaluationId);
        if (!$ev) {
            $this->abort(404, 'Không tìm thấy bài đánh giá.');
        }
        if ((int) $ev['advisor_id'] !== (int) $user['id']) {
            $this->abort(403, 'Sinh viên này không thuộc lớp bạn phụ trách.');
        }
        return $ev;
    }
}
