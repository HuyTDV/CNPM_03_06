<?php
// US-05: cán bộ lớp kiểm tra minh chứng và chấm điểm cấp lớp.
//
// Quy tắc (kiểm tra bằng PHP):
//  - Cán bộ lớp chỉ thấy và chấm sinh viên CỦA LỚP MÌNH.
//  - Chỉ chấm bài đã nộp (submitted), trong thời hạn lớp chấm, khi đợt chưa bị khóa.
//  - Không tự chấm bài của chính mình (bài của cán bộ lớp do GVCN xem xét trực tiếp).
//  - Lưu điểm lớp được phép chấm dở; chỉ "Gửi GVCN" các bài đã chấm ĐỦ mọi khoản.
class ClassController extends Controller
{
    // Tổng quan: các đợt và tiến độ chấm của lớp
    public function dashboard()
    {
        [$user, $student, $class] = $this->mine();
        Period::lockExpired();
        $rows = [];
        foreach (Period::started() as $p) {
            $p['phase'] = Period::phase($p);
            $rows[] = ['period' => $p, 'summary' => Review::summary(Review::students($class['id'], $p['id']))];
        }
        $this->render('class/dashboard', ['pageTitle' => 'Khu vực cán bộ lớp', 'class' => $class, 'rows' => $rows]);
    }

    // Menu "Chấm điểm lớp": chuyển tới đợt mới nhất
    public function current()
    {
        $this->mine();
        $period = Period::latest();
        if (!$period) {
            flash('info', 'Hiện chưa có đợt đánh giá nào.');
            $this->redirect('class/dashboard');
        }
        $this->redirect('class/students/' . $period['id']);
    }

    // Danh sách sinh viên của lớp trong một đợt
    public function students(int $periodId)
    {
        [$user, $student, $class] = $this->mine();
        Period::lockExpired();
        $period = $this->periodOr404($periodId);

        $list = Review::students($class['id'], $periodId);
        foreach ($list as $k => $r) {
            $list[$k]['is_self'] = (int) $r['student_id'] === (int) $student['id'];
            $list[$k]['can_grade'] = $r['evaluation_id']
                && Review::classCanGrade(['status' => $r['status']], $period, $list[$k]['is_self']);
        }
        $this->render('class/students', [
            'pageTitle' => 'Chấm điểm lớp',
            'class'     => $class,
            'period'    => $period,
            'phase'     => Period::phase($period),
            'list'      => $list,
            'summary'   => Review::summary($list),
        ]);
    }

    // Xem minh chứng và nhập điểm lớp cho một sinh viên
    public function grade(int $evaluationId)
    {
        [$user, $student, $class] = $this->mine();
        Period::lockExpired();
        $ev = $this->evaluationOfMyClass($evaluationId, $class);
        $period = Period::find($ev['period_id']);
        $isSelf = (int) $ev['student_id'] === (int) $student['id'];

        $old = $_SESSION['old_review'] ?? [];
        unset($_SESSION['old_review']);
        $this->render('class/grade', [
            'pageTitle' => 'Chấm điểm lớp',
            'ev'        => $ev,
            'period'    => $period,
            'isSelf'    => $isSelf,
            'canEdit'   => Review::classCanGrade($ev, $period, $isSelf),
            'tree'      => Criteria::tree($period['framework_id']),
            'scores'    => Assessment::scores($ev['id']),
            'evidences' => Assessment::evidencesByItem($ev['id']),
            'old'       => $old,
            'scripts'   => ['js/assessment.js'],
        ]);
    }

    // POST: lưu điểm lớp chấm (được bỏ trống một số khoản, sẽ chấm tiếp sau)
    public function saveScores(int $evaluationId)
    {
        [$user, $student, $class] = $this->mine();
        $this->requirePost();
        Period::lockExpired();
        $ev = $this->evaluationOfMyClass($evaluationId, $class);
        $period = Period::find($ev['period_id']);
        $isSelf = (int) $ev['student_id'] === (int) $student['id'];
        $back = 'class/grade/' . $evaluationId;

        if (!Review::classCanGrade($ev, $period, $isSelf)) {
            flash('danger', 'Bạn không thể chấm bài này lúc này (bài của chính bạn, chưa nộp, đã gửi GVCN, hết hạn chấm hoặc đợt đã khóa).');
            $this->redirect($back);
        }

        $items = tree_items(Criteria::tree($period['framework_id']));
        $rawScores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
        $rawNotes = is_array($_POST['notes'] ?? null) ? $_POST['notes'] : [];
        [$rows, $errors] = read_score_rows($items, $rawScores, $rawNotes, false);
        $note = mb_substr($this->post('class_note'), 0, 1000);

        if ($errors) {
            $_SESSION['old_review'] = ['scores' => $rawScores, 'notes' => $rawNotes, 'note' => $note];
            flash('danger', errors_message('Chưa lưu được, vui lòng sửa các lỗi sau:', $errors));
            $this->redirect($back);
        }
        Review::saveClassScores($evaluationId, $rows, score_sum(array_column($rows, 'score')), $note !== '' ? $note : null);
        flash('success', 'Đã lưu điểm lớp. Khi chấm xong cả lớp, hãy bấm "Gửi GVCN" ở trang danh sách.');
        $this->redirect($back);
    }

    // POST: gửi GVCN tất cả bài đã nộp và đã chấm đủ điểm lớp
    public function send(int $periodId)
    {
        [$user, $student, $class] = $this->mine();
        $this->requirePost();
        Period::lockExpired();
        $period = $this->periodOr404($periodId);
        $back = 'class/students/' . $periodId;

        $itemIds = array_keys(tree_items(Criteria::tree($period['framework_id'])));
        $sent = 0;
        $incomplete = [];
        foreach (Review::students($class['id'], $periodId) as $r) {
            $isSelf = (int) $r['student_id'] === (int) $student['id'];
            if (!$r['evaluation_id'] || !Review::classCanGrade(['status' => $r['status']], $period, $isSelf)) {
                continue;                                  // chưa nộp / đã gửi / bài của chính mình / hết hạn
            }
            $scores = Assessment::scores($r['evaluation_id']);
            $graded = 0;
            foreach ($itemIds as $itemId) {
                if (isset($scores[$itemId]) && $scores[$itemId]['class_score'] !== null) {
                    $graded++;
                }
            }
            if ($graded < count($itemIds)) {
                $incomplete[] = $r['student_code'];        // còn khoản chưa chấm
                continue;
            }
            Review::sendToAdvisor($r['evaluation_id'], $user['id']);
            $sent++;
        }

        if ($sent === 0 && !$incomplete) {
            flash('warning', 'Không có bài nào để gửi: chưa có bài đã nộp, hoặc đã gửi hết, hoặc đã quá hạn chấm.');
        } else {
            $msg = "Đã gửi GVCN $sent bài.";
            if ($incomplete) {
                $msg .= ' Chưa gửi do còn khoản chưa chấm điểm lớp: ' . implode(', ', $incomplete) . '.';
            }
            flash($incomplete ? 'warning' : 'success', $msg);
        }
        $this->redirect($back);
    }

    // ---------------------------------------------------------------- Hỗ trợ

    // Người đang đăng nhập phải là cán bộ lớp. Trả về [tài khoản, hồ sơ sinh viên, lớp].
    private function mine()
    {
        $user = $this->requireRole([ROLE_MONITOR]);
        $student = Student::findByUserId($user['id']);
        $class = $student ? Classroom::find($student['class_id']) : null;
        if (!$class) {
            $this->abort(403, 'Tài khoản của bạn chưa gắn với hồ sơ sinh viên và lớp. Vui lòng liên hệ quản trị viên.');
        }
        return [$user, $student, $class];
    }

    private function periodOr404($periodId)
    {
        $period = Period::find($periodId);
        if (!$period || now_str() < $period['start_at']) {
            $this->abort(404, 'Không tìm thấy đợt đánh giá.');
        }
        return $period;
    }

    // Bài đánh giá phải của sinh viên trong lớp mình, nếu không là 403
    private function evaluationOfMyClass($evaluationId, $class)
    {
        $ev = Review::evaluation($evaluationId);
        if (!$ev) {
            $this->abort(404, 'Không tìm thấy bài đánh giá.');
        }
        if ((int) $ev['class_id'] !== (int) $class['id']) {
            $this->abort(403, 'Sinh viên này không thuộc lớp của bạn.');
        }
        return $ev;
    }
}
