<?php
// US-02: sinh viên (và cán bộ lớp, vì cũng là sinh viên) tự đánh giá, lưu nháp, gửi bài, đính kèm minh chứng.
//
// Quy tắc (kiểm tra bằng PHP, không tin dữ liệu từ trình duyệt):
//  - Chỉ sửa được khi Assessment::canEdit() cho phép (đợt mở, còn hạn, bài ở trạng thái nháp / cần chỉnh sửa).
//  - Mỗi điểm phải nằm trong [điểm tối thiểu, điểm tối đa] của khoản; tổng điểm do server tự cộng.
//  - Lưu nháp: được bỏ trống. Gửi bài: phải chấm hết mọi khoản, khoản bắt buộc minh chứng phải có file.
//  - Mỗi khoản tối đa MAX_EVIDENCE_PER_ITEM file; file PDF/JPG/PNG <= 5 MB.
class AssessmentController extends Controller
{
    // Menu "Tự đánh giá": chuyển tới đợt đang mở mới nhất
    public function current()
    {
        $this->requireRole([ROLE_STUDENT, ROLE_MONITOR]);
        Period::lockExpired();
        $period = Period::latestStarted();
        if (!$period) {
            flash('info', 'Hiện chưa có đợt đánh giá nào đang mở.');
            $this->redirect('student/dashboard');
        }
        $this->redirect('assessment/form/' . $period['id']);
    }

    // Hiện form chấm điểm của một đợt
    public function form(int $periodId)
    {
        $user = $this->requireRole([ROLE_STUDENT, ROLE_MONITOR]);
        $student = $this->studentOf($user);
        Period::lockExpired();
        $period = Period::find($periodId);
        if (!$period) {
            $this->abort(404, 'Không tìm thấy đợt đánh giá.');
        }
        if (now_str() < $period['start_at']) {
            flash('info', 'Đợt đánh giá này chưa bắt đầu (từ ' . fmt_datetime($period['start_at']) . ').');
            $this->redirect('student/dashboard');
        }

        $eval = Assessment::findByPeriodStudent($periodId, $student['id']);
        if (!$eval) {
            if (!Period::studentWindowOpen($period)) {
                flash('warning', 'Bạn chưa có bài đánh giá trong đợt này và đợt đã hết hạn hoặc bị khóa.');
                $this->redirect('student/dashboard');
            }
            $eval = Assessment::findOrCreate($periodId, $student['id']);
        }

        $this->render('student/assessment', [
            'pageTitle' => 'Tự đánh giá',
            'period'    => $period,
            'phase'     => Period::phase($period),
            'eval'      => $eval,
            'canEdit'   => Assessment::canEdit($eval, $period),
            'tree'      => Criteria::tree($period['framework_id']),
            'scores'    => Assessment::scores($eval['id']),
            'evidences' => Assessment::evidencesByItem($eval['id']),
            'old'       => $this->pullOld(),
            'scripts'   => ['js/assessment.js'],
        ]);
    }

    // POST: lưu nháp (action=draft) hoặc gửi bài (action=submit)
    public function save(int $periodId)
    {
        $user = $this->requireRole([ROLE_STUDENT, ROLE_MONITOR]);
        $this->requirePost();
        $student = $this->studentOf($user);
        Period::lockExpired();
        $period = Period::find($periodId);
        $eval = $period ? Assessment::findByPeriodStudent($periodId, $student['id']) : null;
        if (!$eval) {
            $this->abort(404, 'Bạn chưa có bài đánh giá trong đợt này.');
        }
        $formUrl = 'assessment/form/' . $periodId;

        if (!Assessment::canEdit($eval, $period)) {
            flash('danger', 'Bài đánh giá hiện không thể chỉnh sửa (đã nộp, đã quá hạn hoặc đợt đã bị khóa).');
            $this->redirect($formUrl);
        }

        $submit = $this->post('action') === 'submit';
        $items = [];                                           // [id_khoản => khoản]
        foreach (Criteria::tree($period['framework_id']) as $cat) {
            foreach ($cat['items'] as $it) {
                $items[$it['id']] = $it;
            }
        }
        $existing = Assessment::evidencesByItem($eval['id']);
        $uploads = collect_uploads();
        $rawScores = is_array($_POST['scores'] ?? null) ? $_POST['scores'] : [];
        $rawNotes = is_array($_POST['notes'] ?? null) ? $_POST['notes'] : [];

        // ---- Kiểm tra từng khoản
        $errors = [];
        $rows = [];
        foreach ($items as $id => $it) {
            $label = 'Khoản ' . $it['code'];
            $raw = is_string($rawScores[$id] ?? null) ? trim($rawScores[$id]) : '';
            $note = is_string($rawNotes[$id] ?? null) ? mb_substr(trim($rawNotes[$id]), 0, 500) : '';
            $score = null;
            if ($raw !== '') {
                $n = to_float($raw);
                if ($n === null) {
                    $errors[] = "$label: điểm không hợp lệ.";
                } elseif ($n < $it['min_score'] || $n > $it['max_score']) {
                    $errors[] = "$label: điểm phải từ " . fmt_score($it['min_score']) . ' đến ' . fmt_score($it['max_score']) . '.';
                } else {
                    $score = round($n, 1);
                }
            } elseif ($submit) {
                $errors[] = "$label: chưa nhập điểm.";
            }
            $rows[$id] = ['score' => $score, 'note' => $note !== '' ? $note : null];

            $fileCount = count($existing[$id] ?? []) + count($uploads[$id] ?? []);
            if ($fileCount > MAX_EVIDENCE_PER_ITEM) {
                $errors[] = "$label: tối đa " . MAX_EVIDENCE_PER_ITEM . ' file minh chứng.';
            }
            if ($submit && $it['require_evidence'] && $fileCount === 0) {
                $errors[] = "$label: bắt buộc đính kèm minh chứng.";
            }
        }
        foreach ($uploads as $itemId => $files) {
            if (!isset($items[$itemId])) {
                unset($uploads[$itemId]);                      // khoản không thuộc khung này: bỏ qua
                continue;
            }
            foreach ($files as $file) {
                if (($msg = check_upload($file)) !== null) {
                    $errors[] = $msg;
                }
            }
        }
        $selfNote = mb_substr($this->post('self_note'), 0, 1000);

        if ($errors) {
            $_SESSION['old_assessment'] = ['scores' => $rawScores, 'notes' => $rawNotes, 'self_note' => $selfNote];
            $shown = array_slice($errors, 0, 8);
            if (count($errors) > 8) {
                $shown[] = '… và ' . (count($errors) - 8) . ' lỗi khác.';
            }
            flash('danger', "Chưa lưu được, vui lòng sửa các lỗi sau (các file chọn lại sau khi sửa):\n" . implode("\n", $shown));
            $this->redirect($formUrl);
        }

        // ---- Lưu: (1) file lên đĩa, (2) điểm + minh chứng + trạng thái vào CSDL trong một transaction
        $saved = [];
        try {
            foreach ($uploads as $itemId => $files) {
                foreach ($files as $file) {
                    $saved[] = [$itemId, save_upload($file)];
                }
            }
            Model::transaction(function () use ($saved, $rows, $eval, $selfNote, $submit, $user) {
                foreach ($saved as [$itemId, $meta]) {
                    Assessment::addEvidence($eval['id'], $itemId, $meta, $user['id']);
                }
                Assessment::saveScores($eval['id'], $rows);
                Assessment::saveSelf($eval['id'], score_sum(array_column($rows, 'score')), $selfNote !== '' ? $selfNote : null);
                if ($submit) {
                    Assessment::markSubmitted($eval['id']);
                }
            });
        } catch (Exception $e) {
            foreach ($saved as [, $meta]) {
                delete_upload($meta['stored_name']);           // lỗi giữa chừng: không để lại file mồ côi
            }
            throw $e;
        }

        if ($submit) {
            flash('success', 'Đã gửi bài đánh giá. Lớp sẽ kiểm tra minh chứng và chấm điểm.');
            $this->redirect('student/dashboard');
        }
        flash('success', 'Đã lưu bản nháp.');
        $this->redirect($formUrl);
    }

    // ---------------------------------------------------------------- Hỗ trợ

    // Hồ sơ sinh viên của người đang đăng nhập (cán bộ lớp cũng có hồ sơ sinh viên)
    private function studentOf($user)
    {
        $student = Student::findByUserId($user['id']);
        if (!$student) {
            $this->abort(403, 'Tài khoản của bạn chưa gắn với hồ sơ sinh viên. Vui lòng liên hệ quản trị viên.');
        }
        return $student;
    }

    // Dữ liệu form lần gửi trước (khi có lỗi) để điền lại, chỉ dùng một lần
    private function pullOld()
    {
        $old = $_SESSION['old_assessment'] ?? [];
        unset($_SESSION['old_assessment']);
        return $old;
    }
}
