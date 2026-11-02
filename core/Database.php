<?php
// Kết nối MySQL bằng PDO. Dùng: Database::pdo()
class Database
{
    private static $pdo = null;

    public static function pdo()
    {
        if (self::$pdo === null) {
            $c = require BASE_PATH . '/config/database.php';
            $dsn = "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4";
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // lỗi SQL -> ném exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // kết quả là mảng có tên cột
                PDO::ATTR_EMULATE_PREPARES   => false,                    // dùng prepared statement thật
            ]);
        }
        return self::$pdo;
    }
}
