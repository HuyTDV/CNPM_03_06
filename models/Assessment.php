<?php
// Bài đánh giá của sinh viên: evaluations (bài), evaluation_scores (điểm từng khoản), evidences (minh chứng).
class Assessment extends Model
{
    // ------------------------------------------------------------ Bài đánh giá
    public static function find($id)
    {
        return self::one('SELECT * FROM evaluations WHERE id = ?', [$id]);
    }

    public static function findByPeriodStudent($periodId, $studentId)
    {
        return self::one('SELECT * FROM evaluations WHERE period_id = ? AND student_id = ?', [$periodId, $studentId]);
    }

    // Lấy bài đánh giá, chưa có thì tạo bản nháp (UNIQUE period+student chống tạo trùng khi bấm 2 lần)
    public static function findOrCreate($periodId, $studentId)
    {
        $eval = self::findByPeriodStudent($periodId, $studentId);
        if ($eval === null) {
            try {
                self::insert('INSERT INTO evaluations (period_id, student_id, status) VALUES (?, ?, ?)', [$periodId, $studentId, EVAL_DRAFT]);
            } catch (PDOException $e) {
                if (self::errorCode($e) !== 1062) {
                    throw $e;
                }
            }
            $eval = self::findByPeriodStudent($periodId, $studentId);
        }
        return $eval;
    }

    // Ai là chủ bài, lớp nào, GVCN nào (để kiểm quyền xem minh chứng)
    public static function context($id)
    {
        return self::one(
            'SELECT e.id, e.period_id, e.student_id, s.user_id, s.class_id, c.advisor_id
               FROM evaluations e JOIN students s ON s.id = e.student_id JOIN classes c ON c.id = s.class_id
              WHERE e.id = ?',
            [$id]
        );
    }

    public static function saveSelf($id, $total, $note)
    {
        self::run('UPDATE evaluations SET self_total = ?, self_note = ? WHERE id = ?', [$total, $note, $id]);
    }

    public static function markSubmitted($id)
    {
        self::run('UPDATE evaluations SET status = ?, submitted_at = ? WHERE id = ?', [EVAL_SUBMITTED, now_str(), $id]);
    }

    // Sinh viên có được sửa bài lúc này không?
    //  - Đợt phải đang mở (chưa khóa).
    //  - Bản nháp: sửa đến hạn sinh viên nộp. Bị yêu cầu chỉnh sửa: sửa đến hạn GVCN duyệt.
    //  - Đã nộp / đã chấm / đã duyệt: không sửa.
    public static function canEdit($eval, $period)
    {
        $now = now_str();
        if ($period['status'] !== PERIOD_OPEN || $now < $period['start_at']) {
            return false;
        }
        if ($eval['status'] === EVAL_DRAFT) {
            return $now <= $period['student_end_at'];
        }
        if ($eval['status'] === EVAL_NEED_EDIT) {
            return $now <= $period['advisor_end_at'];
        }
        return false;
    }

    // ------------------------------------------------------------ Điểm từng khoản
    // Trả về [id_khoản => dòng điểm]
    public static function scores($evaluationId)
    {
        $map = [];
        foreach (self::all('SELECT * FROM evaluation_scores WHERE evaluation_id = ?', [$evaluationId]) as $row) {
            $map[$row['item_id']] = $row;
        }
        return $map;
    }

    // $rows: [id_khoản => ['score' => số|null, 'note' => chữ|null]]. Có rồi thì cập nhật, chưa có thì thêm.
    public static function saveScores($evaluationId, $rows)
    {
        $st = Database::pdo()->prepare(
            'INSERT INTO evaluation_scores (evaluation_id, item_id, self_score, self_note) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE self_score = VALUES(self_score), self_note = VALUES(self_note)'
        );
        foreach ($rows as $itemId => $r) {
            $st->execute([$evaluationId, $itemId, $r['score'], $r['note']]);
        }
    }

    // ------------------------------------------------------------ Minh chứng
    public static function evidence($id)
    {
        return self::one('SELECT * FROM evidences WHERE id = ?', [$id]);
    }

    // Trả về [id_khoản => [file, file, ...]]
    public static function evidencesByItem($evaluationId)
    {
        $out = [];
        foreach (self::all('SELECT * FROM evidences WHERE evaluation_id = ? ORDER BY id', [$evaluationId]) as $row) {
            $out[$row['item_id']][] = $row;
        }
        return $out;
    }

    public static function addEvidence($evaluationId, $itemId, $meta, $userId)
    {
        return self::insert(
            'INSERT INTO evidences (evaluation_id, item_id, original_name, stored_name, file_ext, mime_type, file_size, uploaded_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$evaluationId, $itemId, $meta['original_name'], $meta['stored_name'], $meta['file_ext'],
             $meta['mime_type'], $meta['file_size'], $userId]
        );
    }

    public static function deleteEvidence($id)
    {
        self::run('DELETE FROM evidences WHERE id = ?', [$id]);
    }
}
