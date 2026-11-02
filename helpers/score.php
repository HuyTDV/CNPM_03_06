<?php
// Mọi phép tính điểm nằm ở đây (không tính trong SQL).

// "7,5" hoặc "7.5" -> 7.5; không phải số -> null
function to_float($raw)
{
    $raw = str_replace(',', '.', trim((string) $raw));
    return preg_match('/^-?\d+(\.\d+)?$/', $raw) ? (float) $raw : null;
}

// Tổng các điểm (bỏ qua null), làm tròn 2 chữ số để tránh sai số số thực
function score_sum($scores)
{
    $sum = 0.0;
    foreach ($scores as $s) {
        if ($s !== null && $s !== '') {
            $sum += (float) $s;
        }
    }
    return round($sum, 2);
}

// Kiểm tra một khung điểm đã dùng được chưa. Trả về danh sách vấn đề (rỗng = hợp lệ).
// $tree là kết quả Criteria::tree(): mỗi điều có mảng 'items'.
function framework_problems($framework, $tree)
{
    if (!$tree) {
        return ['Khung điểm chưa có điều nào.'];
    }
    $problems = [];
    $categorySum = 0.0;
    foreach ($tree as $cat) {
        $categorySum += (float) $cat['max_score'];
        if (!$cat['items']) {
            $problems[] = 'Điều ' . $cat['code'] . ' chưa có khoản nào.';
            continue;
        }
        $itemSum = score_sum(array_column($cat['items'], 'max_score'));
        if (abs($itemSum - (float) $cat['max_score']) > 0.001) {
            $problems[] = 'Điều ' . $cat['code'] . ': tổng điểm các khoản là ' . fmt_score($itemSum)
                . ' nhưng điểm tối đa của điều là ' . fmt_score($cat['max_score']) . '.';
        }
    }
    if (abs($categorySum - (float) $framework['total_max_score']) > 0.001) {
        $problems[] = 'Tổng điểm các điều là ' . fmt_score($categorySum)
            . ' nhưng tổng điểm tối đa của khung là ' . fmt_score($framework['total_max_score']) . '.';
    }
    return $problems;
}

// Xếp loại theo bảng RATINGS trong config/constants.php: rating_for(85) -> "Tốt"
function rating_for($total)
{
    foreach (RATINGS as [$min, $name]) {
        if ($total >= $min) {
            return $name;
        }
    }
    return RATINGS[count(RATINGS) - 1][1];
}

// Danh sách phẳng các khoản của một cây tiêu chí: [id_khoản => khoản]
function tree_items($tree)
{
    $items = [];
    foreach ($tree as $cat) {
        foreach ($cat['items'] as $it) {
            $items[$it['id']] = $it;
        }
    }
    return $items;
}

// Đọc điểm từ form chấm (cấp lớp hoặc GVCN): scores[id] và notes[id].
// Mỗi điểm phải nằm trong [điểm tối thiểu, điểm tối đa] của khoản. $requireAll = true: phải chấm đủ mọi khoản.
// Trả về [$rows, $errors]; $rows: [id_khoản => ['score' => số|null, 'note' => chữ|null]]
function read_score_rows($items, $rawScores, $rawNotes, $requireAll)
{
    $rows = [];
    $errors = [];
    foreach ($items as $id => $it) {
        $label = 'Khoản ' . $it['code'];
        $raw = is_string($rawScores[$id] ?? null) ? trim($rawScores[$id]) : '';
        $note = is_string($rawNotes[$id] ?? null) ? mb_substr(trim($rawNotes[$id]), 0, 500) : '';
        $score = null;
        if ($raw !== '') {
            $n = to_float($raw);
            if ($n === null) {
                $errors[] = "$label: điểm không hợp lệ.";
            } elseif ($n < $it['min_score'] || $n > $it['max_score']) {
                $errors[] = "$label: điểm phải từ " . fmt_score($it['min_score']) . ' đến ' . fmt_score($it['max_score']) . '.';
            } else {
                $score = round($n, 1);
            }
        } elseif ($requireAll) {
            $errors[] = "$label: chưa nhập điểm.";
        }
        $rows[$id] = ['score' => $score, 'note' => $note !== '' ? $note : null];
    }
    return [$rows, $errors];
}

// Gộp tối đa 8 lỗi thành một thông báo
function errors_message($intro, $errors)
{
    $shown = array_slice($errors, 0, 8);
    if (count($errors) > 8) {
        $shown[] = '… và ' . (count($errors) - 8) . ' lỗi khác.';
    }
    return $intro . "\n" . implode("\n", $shown);
}
