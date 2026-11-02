<?php
// Bảng approvals: lịch sử chuyển bài giữa các cấp (chỉ thêm, không sửa/xóa).
//   level : class = lớp, advisor = GVCN, school = nhà trường
//   action: submit = gửi lên cấp trên, approve = duyệt, request_edit = yêu cầu chỉnh sửa (bắt buộc có lý do)
class Approval extends Model
{
    public static function add($evaluationId, $level, $action, $reason, $actorId)
    {
        return self::insert(
            'INSERT INTO approvals (evaluation_id, level, action, reason, actor_id, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$evaluationId, $level, $action, $reason, $actorId, now_str()]
        );
    }

    // Lịch sử của một bài, cũ nhất trước, kèm tên người thực hiện
    public static function history($evaluationId)
    {
        return self::all(
            'SELECT a.*, u.full_name AS actor_name FROM approvals a LEFT JOIN users u ON u.id = a.actor_id
              WHERE a.evaluation_id = ? ORDER BY a.created_at, a.id',
            [$evaluationId]
        );
    }
}
