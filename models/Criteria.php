<?php
// Khung điểm (criteria_frameworks), điều (criteria_categories), khoản (criteria_items).
class Criteria extends Model
{
    // ------------------------------------------------------------ Khung điểm
    public static function frameworks()
    {
        return self::all(
            'SELECT f.*, s.name AS semester_name FROM criteria_frameworks f
               JOIN semesters s ON s.id = f.semester_id ORDER BY s.start_date DESC, f.id DESC'
        );
    }

    // Các khung đang bật (để chọn khi tạo đợt đánh giá)
    public static function activeFrameworks()
    {
        return self::all(
            'SELECT f.*, s.name AS semester_name FROM criteria_frameworks f
               JOIN semesters s ON s.id = f.semester_id WHERE f.is_active = 1 ORDER BY s.start_date DESC'
        );
    }

    public static function framework($id)
    {
        return self::one(
            'SELECT f.*, s.name AS semester_name FROM criteria_frameworks f
               JOIN semesters s ON s.id = f.semester_id WHERE f.id = ?',
            [$id]
        );
    }

    public static function createFramework($semesterId, $name, $description, $total, $userId)
    {
        return self::insert(
            'INSERT INTO criteria_frameworks (semester_id, name, description, total_max_score, is_active, created_by)
             VALUES (?, ?, ?, ?, 0, ?)',
            [$semesterId, $name, $description, $total, $userId]
        );
    }

    public static function updateFramework($id, $semesterId, $name, $description, $total)
    {
        self::run(
            'UPDATE criteria_frameworks SET semester_id = ?, name = ?, description = ?, total_max_score = ? WHERE id = ?',
            [$semesterId, $name, $description, $total, $id]
        );
    }

    public static function deleteFramework($id)
    {
        self::run('DELETE FROM criteria_frameworks WHERE id = ?', [$id]);
    }

    // Bật khung: tắt các khung khác của cùng học kỳ rồi bật khung này (mỗi học kỳ chỉ 1 khung bật)
    public static function activate($id, $semesterId)
    {
        self::transaction(function () use ($id, $semesterId) {
            self::run('UPDATE criteria_frameworks SET is_active = 0 WHERE semester_id = ?', [$semesterId]);
            self::run('UPDATE criteria_frameworks SET is_active = 1 WHERE id = ?', [$id]);
        });
    }

    public static function deactivate($id)
    {
        self::run('UPDATE criteria_frameworks SET is_active = 0 WHERE id = ?', [$id]);
    }

    // Có đợt đánh giá nào đang dùng khung này không?
    public static function usedByPeriods($id)
    {
        return self::one('SELECT 1 AS x FROM evaluation_periods WHERE framework_id = ? LIMIT 1', [$id]) !== null;
    }

    // Đã có sinh viên bắt đầu đánh giá theo khung này chưa? (có rồi thì không được đổi điểm/cấu trúc)
    public static function hasEvaluations($id)
    {
        return self::one(
            'SELECT 1 AS x FROM evaluations e JOIN evaluation_periods p ON p.id = e.period_id
              WHERE p.framework_id = ? LIMIT 1',
            [$id]
        ) !== null;
    }

    // Cây tiêu chí: danh sách điều, mỗi điều có mảng 'items'
    public static function tree($frameworkId)
    {
        $cats = self::all('SELECT * FROM criteria_categories WHERE framework_id = ? ORDER BY sort_order, id', [$frameworkId]);
        $items = self::all(
            'SELECT i.* FROM criteria_items i JOIN criteria_categories c ON c.id = i.category_id
              WHERE c.framework_id = ? ORDER BY i.sort_order, i.id',
            [$frameworkId]
        );
        foreach ($cats as $k => $cat) {
            $cats[$k]['items'] = [];
            foreach ($items as $it) {
                if ($it['category_id'] == $cat['id']) {
                    $cats[$k]['items'][] = $it;
                }
            }
        }
        return $cats;
    }

    // Sao chép cả khung (điều + khoản) sang học kỳ khác; khung mới ở trạng thái tắt
    public static function copyFramework($srcId, $semesterId, $name, $userId)
    {
        return self::transaction(function () use ($srcId, $semesterId, $name, $userId) {
            $src = self::framework($srcId);
            $newId = self::createFramework($semesterId, $name, $src['description'], $src['total_max_score'], $userId);
            foreach (self::tree($srcId) as $cat) {
                $catId = self::createCategory($newId, $cat['code'], $cat['title'], $cat['description'], $cat['max_score'], $cat['sort_order']);
                foreach ($cat['items'] as $it) {
                    self::createItem($catId, $it['code'], $it['content'], $it['max_score'], $it['min_score'], $it['require_evidence'], $it['sort_order']);
                }
            }
            return $newId;
        });
    }

    // ------------------------------------------------------------ Điều
    public static function category($id)
    {
        return self::one('SELECT * FROM criteria_categories WHERE id = ?', [$id]);
    }

    public static function categories($frameworkId)
    {
        return self::all('SELECT * FROM criteria_categories WHERE framework_id = ? ORDER BY sort_order, id', [$frameworkId]);
    }

    public static function createCategory($frameworkId, $code, $title, $description, $max, $sort)
    {
        return self::insert(
            'INSERT INTO criteria_categories (framework_id, code, title, description, max_score, sort_order) VALUES (?, ?, ?, ?, ?, ?)',
            [$frameworkId, $code, $title, $description, $max, $sort]
        );
    }

    public static function updateCategory($id, $code, $title, $description, $max)
    {
        self::run('UPDATE criteria_categories SET code = ?, title = ?, description = ?, max_score = ? WHERE id = ?',
            [$code, $title, $description, $max, $id]);
    }

    public static function deleteCategory($id)
    {
        self::run('DELETE FROM criteria_categories WHERE id = ?', [$id]);
    }

    // ------------------------------------------------------------ Khoản
    // Kèm framework_id của điều chứa nó
    public static function item($id)
    {
        return self::one(
            'SELECT i.*, c.framework_id FROM criteria_items i JOIN criteria_categories c ON c.id = i.category_id WHERE i.id = ?',
            [$id]
        );
    }

    public static function items($categoryId)
    {
        return self::all('SELECT * FROM criteria_items WHERE category_id = ? ORDER BY sort_order, id', [$categoryId]);
    }

    public static function createItem($categoryId, $code, $content, $max, $min, $requireEvidence, $sort)
    {
        return self::insert(
            'INSERT INTO criteria_items (category_id, code, content, max_score, min_score, require_evidence, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$categoryId, $code, $content, $max, $min, $requireEvidence, $sort]
        );
    }

    public static function updateItem($id, $code, $content, $max, $min, $requireEvidence)
    {
        self::run('UPDATE criteria_items SET code = ?, content = ?, max_score = ?, min_score = ?, require_evidence = ? WHERE id = ?',
            [$code, $content, $max, $min, $requireEvidence, $id]);
    }

    public static function deleteItem($id)
    {
        self::run('DELETE FROM criteria_items WHERE id = ?', [$id]);
    }
}
