<?php
// Chấm điểm cấp lớp (US-05) và GVCN duyệt (US-06): làm việc trên bảng evaluations và evaluation_scores.
class Review extends Model
{
    // Tất cả sinh viên của lớp, kèm bài đánh giá trong đợt (nếu có; chưa có thì cột evaluation_id là null)
    public static function students($classId, $periodId)
    {
        return self::all(
            'SELECT s.id AS student_id, s.student_code, u.full_name, e.id AS evaluation_id, e.status, e.self_total,
                    e.class_total, e.advisor_total, e.final_total, e.rating, e.submitted_at
               FROM students s
               JOIN users u ON u.id = s.user_id
               LEFT JOIN evaluations e ON e.student_id = s.id AND e.period_id = ?
              WHERE s.class_id = ? AND s.status = ? ORDER BY s.student_code',
            [$periodId, $classId, 'studying']
        );
    }

    // Một bài đánh giá kèm thông tin sinh viên, lớp, GVCN và cán bộ lớp của lớp đó
    public static function evaluation($id)
    {
        return self::one(
            'SELECT e.*, s.student_code, s.class_id, u.full_name, c.code AS class_code, c.advisor_id, c.monitor_id
               FROM evaluations e
               JOIN students s ON s.id = e.student_id
               JOIN users u    ON u.id = s.user_id
               JOIN classes c  ON c.id = s.class_id
              WHERE e.id = ?',
            [$id]
        );
    }

    // Đếm số sinh viên theo trạng thái (tính bằng PHP từ kết quả Review::students)
    public static function summary($list)
    {
        $s = ['total' => count($list), 'not_started' => 0, 'draft' => 0, 'submitted' => 0,
              'class_graded' => 0, 'need_edit' => 0, 'approved' => 0];
        foreach ($list as $r) {
            if ($r['status'] === null)                     $s['not_started']++;
            elseif ($r['status'] === EVAL_DRAFT)           $s['draft']++;
            elseif ($r['status'] === EVAL_SUBMITTED)       $s['submitted']++;
            elseif ($r['status'] === EVAL_CLASS_GRADED)    $s['class_graded']++;
            elseif ($r['status'] === EVAL_NEED_EDIT)       $s['need_edit']++;
            else                                           $s['approved']++;
        }
        return $s;
    }

    // ---------------------------------------------------------------- Quyền thao tác (theo trạng thái và thời hạn)

    // Cán bộ lớp chấm được không? Bài phải ở trạng thái "đã nộp", không phải bài của chính mình,
    // đợt đang mở và chưa quá hạn lớp chấm.
    public static function classCanGrade($eval, $period, $isSelf)
    {
        $now = now_str();
        return !$isSelf && $eval['status'] === EVAL_SUBMITTED && $period['status'] === PERIOD_OPEN
            && $now >= $period['start_at'] && $now <= $period['class_end_at'];
    }

    // GVCN duyệt được không? Đợt đang mở, chưa quá hạn GVCN duyệt, và bài thuộc một trong các trường hợp:
    //  - lớp đã chấm xong (class_graded);
    //  - bài của chính cán bộ lớp (không ai ở lớp chấm thay được);
    //  - bài nộp lại sau khi đã quá hạn lớp chấm (lớp không còn chấm được nữa).
    public static function advisorCanReview($eval, $period)
    {
        $now = now_str();
        if ($period['status'] !== PERIOD_OPEN || $now > $period['advisor_end_at']) {
            return false;
        }
        if ($eval['status'] === EVAL_CLASS_GRADED) {
            return true;
        }
        $isMonitorOwn = (int) $eval['monitor_id'] === (int) $eval['student_id'];
        return $eval['status'] === EVAL_SUBMITTED && ($isMonitorOwn || $now > $period['class_end_at']);
    }

    // ---------------------------------------------------------------- Cấp lớp (US-05)

    // Lưu điểm lớp chấm. $rows: [id_khoản => ['score' => số|null, 'note' => chữ|null]]
    public static function saveClassScores($evaluationId, $rows, $total, $note)
    {
        self::transaction(function () use ($evaluationId, $rows, $total, $note) {
            $st = Database::pdo()->prepare(
                'INSERT INTO evaluation_scores (evaluation_id, item_id, class_score, class_note) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE class_score = VALUES(class_score), class_note = VALUES(class_note)'
            );
            foreach ($rows as $itemId => $r) {
                $st->execute([$evaluationId, $itemId, $r['score'], $r['note']]);
            }
            self::run('UPDATE evaluations SET class_total = ?, class_note = ? WHERE id = ?', [$total, $note, $evaluationId]);
        });
    }

    // Gửi bài lên GVCN: chuyển trạng thái và ghi lịch sử
    public static function sendToAdvisor($evaluationId, $userId)
    {
        self::transaction(function () use ($evaluationId, $userId) {
            self::run('UPDATE evaluations SET status = ?, class_graded_at = ?, class_graded_by = ? WHERE id = ?',
                [EVAL_CLASS_GRADED, now_str(), $userId, $evaluationId]);
            Approval::add($evaluationId, 'class', 'submit', null, $userId);
        });
    }

    // ---------------------------------------------------------------- GVCN (US-06)

    // Duyệt: lưu điểm GVCN từng khoản, tính tổng và xếp loại, chuyển trạng thái, ghi lịch sử.
    // Điểm GVCN duyệt trở thành điểm chính thức (final_score, final_total).
    // $rows: [id_khoản => ['score' => số, 'note' => chữ|null]]
    public static function approve($evaluationId, $rows, $note, $userId)
    {
        $total = score_sum(array_column($rows, 'score'));
        $rating = rating_for($total);
        self::transaction(function () use ($evaluationId, $rows, $note, $userId, $total, $rating) {
            $st = Database::pdo()->prepare(
                'INSERT INTO evaluation_scores (evaluation_id, item_id, advisor_score, advisor_note, final_score) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE advisor_score = VALUES(advisor_score), advisor_note = VALUES(advisor_note),
                                         final_score = VALUES(final_score)'
            );
            foreach ($rows as $itemId => $r) {
                $st->execute([$evaluationId, $itemId, $r['score'], $r['note'], $r['score']]);
            }
            self::run(
                'UPDATE evaluations SET status = ?, advisor_total = ?, final_total = ?, rating = ?, advisor_note = ?,
                        advisor_approved_at = ?, advisor_approved_by = ? WHERE id = ?',
                [EVAL_ADVISOR_APPROVED, $total, $total, $rating, $note, now_str(), $userId, $evaluationId]
            );
            Approval::add($evaluationId, 'advisor', 'approve', $note, $userId);
        });
        return [$total, $rating];
    }

    // Yêu cầu sinh viên chỉnh sửa: bắt buộc có lý do (kiểm tra ở controller). Lý do hiện cho sinh viên đọc.
    public static function requestEdit($evaluationId, $reason, $userId)
    {
        self::transaction(function () use ($evaluationId, $reason, $userId) {
            self::run('UPDATE evaluations SET status = ?, advisor_note = ? WHERE id = ?', [EVAL_NEED_EDIT, $reason, $evaluationId]);
            Approval::add($evaluationId, 'advisor', 'request_edit', $reason, $userId);
        });
    }
}
