<?php
// Bảng classes (kèm khoa).
class Classroom extends Model
{
    public static function find($id)
    {
        return self::one(
            'SELECT c.*, f.name AS faculty_name FROM classes c JOIN faculties f ON f.id = c.faculty_id WHERE c.id = ?',
            [$id]
        );
    }

    // Các lớp mà một giáo viên chủ nhiệm phụ trách
    public static function forAdvisor($userId)
    {
        return self::all(
            'SELECT c.*, f.name AS faculty_name FROM classes c JOIN faculties f ON f.id = c.faculty_id
              WHERE c.advisor_id = ? ORDER BY c.code',
            [$userId]
        );
    }
}
