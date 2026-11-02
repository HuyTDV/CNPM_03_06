<?php
// Bảng students (kèm lớp, khoa).
class Student extends Model
{
    public static function findByUserId($userId)
    {
        return self::one(
            'SELECT s.*, c.code AS class_code, c.cohort, f.name AS faculty_name
               FROM students s
               JOIN classes c   ON c.id = s.class_id
               JOIN faculties f ON f.id = c.faculty_id
              WHERE s.user_id = ?',
            [$userId]
        );
    }
}
