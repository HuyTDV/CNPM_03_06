<?php
// Bảng semesters (học kỳ).
class Semester extends Model
{
    public static function listAll()
    {
        return self::all('SELECT * FROM semesters ORDER BY start_date DESC');
    }

    public static function find($id)
    {
        return self::one('SELECT * FROM semesters WHERE id = ?', [$id]);
    }

    public static function current()
    {
        return self::one('SELECT * FROM semesters WHERE is_current = 1 ORDER BY start_date DESC LIMIT 1');
    }
}
