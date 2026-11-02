<?php
// Bảng evaluation_periods: đợt đánh giá và 4 mốc thời gian.
class Period extends Model
{
    const SELECT = 'SELECT p.*, s.name AS semester_name, f.name AS framework_name
                      FROM evaluation_periods p
                      JOIN semesters s ON s.id = p.semester_id
                      JOIN criteria_frameworks f ON f.id = p.framework_id';

    public static function listAll()
    {
        return self::all(self::SELECT . ' ORDER BY p.start_at DESC, p.id DESC');
    }

    public static function find($id)
    {
        return self::one(self::SELECT . ' WHERE p.id = ?', [$id]);
    }

    // $d: name, semester_id, framework_id, start_at, student_end_at, class_end_at, advisor_end_at, note
    public static function create($d, $userId)
    {
        return self::insert(
            'INSERT INTO evaluation_periods (name, semester_id, framework_id, start_at, student_end_at, class_end_at,
                                             advisor_end_at, status, note, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['name'], $d['semester_id'], $d['framework_id'], $d['start_at'], $d['student_end_at'],
             $d['class_end_at'], $d['advisor_end_at'], PERIOD_OPEN, $d['note'], $userId]
        );
    }

    public static function update($id, $d)
    {
        self::run(
            'UPDATE evaluation_periods SET name = ?, semester_id = ?, framework_id = ?, start_at = ?, student_end_at = ?,
                    class_end_at = ?, advisor_end_at = ?, note = ? WHERE id = ?',
            [$d['name'], $d['semester_id'], $d['framework_id'], $d['start_at'], $d['student_end_at'],
             $d['class_end_at'], $d['advisor_end_at'], $d['note'], $id]
        );
    }

    public static function delete($id)
    {
        self::run('DELETE FROM evaluation_periods WHERE id = ?', [$id]);
    }

    public static function lock($id)
    {
        self::run('UPDATE evaluation_periods SET status = ?, locked_at = ? WHERE id = ?', [PERIOD_LOCKED, now_str(), $id]);
    }

    public static function unlock($id)
    {
        self::run('UPDATE evaluation_periods SET status = ?, locked_at = NULL WHERE id = ?', [PERIOD_OPEN, $id]);
    }

    // Tự khóa các đợt đã quá hạn GVCN duyệt. Gọi mỗi khi mở trang liên quan (không cần cron).
    public static function lockExpired()
    {
        self::run('UPDATE evaluation_periods SET status = ?, locked_at = ? WHERE status = ? AND advisor_end_at < ?',
            [PERIOD_LOCKED, now_str(), PERIOD_OPEN, now_str()]);
    }

    public static function hasEvaluations($id)
    {
        return self::one('SELECT 1 AS x FROM evaluations WHERE period_id = ? LIMIT 1', [$id]) !== null;
    }

    // Đợt đang mở và đã bắt đầu, mới nhất (cho nút "Tự đánh giá")
    public static function latestStarted()
    {
        return self::one(self::SELECT . ' WHERE p.status = ? AND p.start_at <= ? ORDER BY p.start_at DESC, p.id DESC LIMIT 1',
            [PERIOD_OPEN, now_str()]);
    }

    // Đợt đã bắt đầu mới nhất, kể cả đã khóa (cho menu của cán bộ lớp và GVCN)
    public static function latest()
    {
        return self::one(self::SELECT . ' WHERE p.start_at <= ? ORDER BY p.start_at DESC, p.id DESC LIMIT 1', [now_str()]);
    }

    // Các đợt đã bắt đầu (mới nhất trước), dùng cho trang tổng quan của cán bộ lớp và GVCN
    public static function started()
    {
        return self::all(self::SELECT . ' WHERE p.start_at <= ? ORDER BY p.start_at DESC, p.id DESC', [now_str()]);
    }

    // Các đợt đã bắt đầu, kèm bài đánh giá của sinh viên (nếu có), cho trang tổng quan
    public static function listForStudent($studentId)
    {
        return self::all(
            'SELECT p.*, s.name AS semester_name, e.id AS evaluation_id, e.status AS evaluation_status,
                    e.self_total, e.advisor_total, e.final_total, e.rating
               FROM evaluation_periods p
               JOIN semesters s ON s.id = p.semester_id
               LEFT JOIN evaluations e ON e.period_id = p.id AND e.student_id = ?
              WHERE p.start_at <= ? ORDER BY p.start_at DESC, p.id DESC',
            [$studentId, now_str()]
        );
    }

    // Giai đoạn hiện tại của đợt (tính bằng PHP từ trạng thái + 4 mốc thời gian)
    public static function phase($p)
    {
        $now = now_str();
        if ($p['status'] === PERIOD_LOCKED) return 'locked';
        if ($now < $p['start_at'])          return 'not_started';
        if ($now <= $p['student_end_at'])   return 'student';
        if ($now <= $p['class_end_at'])     return 'class';
        if ($now <= $p['advisor_end_at'])   return 'advisor';
        return 'ended';
    }

    // Sinh viên có được bắt đầu / nộp bài lần đầu trong đợt này không?
    public static function studentWindowOpen($p)
    {
        $now = now_str();
        return $p['status'] === PERIOD_OPEN && $now >= $p['start_at'] && $now <= $p['student_end_at'];
    }
}
