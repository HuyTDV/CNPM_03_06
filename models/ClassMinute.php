<?php
// Bảng class_minutes: biên bản họp lớp (mỗi lớp một biên bản cho mỗi đợt).
class ClassMinute extends Model
{
    public static function find($classId, $periodId)
    {
        return self::one('SELECT * FROM class_minutes WHERE class_id = ? AND period_id = ?', [$classId, $periodId]);
    }

    // Có rồi thì cập nhật, chưa có thì thêm (UNIQUE class_id + period_id)
    public static function save($classId, $periodId, $d, $userId)
    {
        self::run(
            'INSERT INTO class_minutes (class_id, period_id, meeting_at, venue, chairperson, secretary, present_count,
                                        total_count, content, conclusion, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE meeting_at = VALUES(meeting_at), venue = VALUES(venue), chairperson = VALUES(chairperson),
                 secretary = VALUES(secretary), present_count = VALUES(present_count), total_count = VALUES(total_count),
                 content = VALUES(content), conclusion = VALUES(conclusion)',
            [$classId, $periodId, $d['meeting_at'], $d['venue'], $d['chairperson'], $d['secretary'], $d['present_count'],
             $d['total_count'], $d['content'], $d['conclusion'], $userId]
        );
    }
}
