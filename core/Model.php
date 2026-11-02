<?php
// Lớp cha của các model. Model chỉ chứa câu SQL (prepared statement); phép tính làm bằng PHP.
// LƯU Ý: hàm của model con đừng đặt tên all/one/run/insert (trùng hàm bên dưới).
class Model
{
    // Nhiều dòng -> mảng các mảng
    protected static function all($sql, $params = [])
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    // Một dòng -> mảng, không có thì null
    protected static function one($sql, $params = [])
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        $row = $st->fetch();
        return $row ?: null;
    }

    // INSERT/UPDATE/DELETE -> số dòng bị ảnh hưởng
    protected static function run($sql, $params = [])
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    // INSERT -> id vừa tạo
    protected static function insert($sql, $params = [])
    {
        Database::pdo()->prepare($sql)->execute($params);
        return (int) Database::pdo()->lastInsertId();
    }

    // Chạy nhiều lệnh như một khối: lỗi giữa chừng thì hoàn tác tất cả
    public static function transaction($fn)
    {
        $db = Database::pdo();
        $db->beginTransaction();
        try {
            $result = $fn();
            $db->commit();
            return $result;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    // Mã lỗi MySQL của một PDOException: 1062 = trùng giá trị duy nhất, 1451 = đang được bảng khác dùng
    public static function errorCode($e)
    {
        return (int) ($e->errorInfo[1] ?? 0);
    }
}
