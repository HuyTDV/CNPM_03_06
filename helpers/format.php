<?php
// Định dạng ngày giờ, điểm, nhãn trạng thái.

// Thời điểm hiện tại theo dạng của CSDL (dùng PHP, không dùng NOW() của SQL)
function now_str()
{
    return date('Y-m-d H:i:s');
}

function fmt_datetime($s)
{
    return $s ? date('d/m/Y H:i', strtotime($s)) : '—';
}

// Giá trị cho ô <input type="datetime-local">
function datetime_input($s)
{
    return $s ? date('Y-m-d\TH:i', strtotime($s)) : '';
}

// Đọc ngày giờ từ ô datetime-local: "2026-12-31T23:59" -> "2026-12-31 23:59:00"; sai định dạng -> null
function to_datetime($raw)
{
    $raw = trim(str_replace('T', ' ', (string) $raw));
    foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
        $d = DateTime::createFromFormat($format, $raw);
        if ($d && $d->format($format) === $raw) {
            return $d->format('Y-m-d H:i:s');
        }
    }
    return null;
}

// Hiển thị điểm: 85.50 -> "85,5"; 10.00 -> "10"; null -> "—"
function fmt_score($n)
{
    if ($n === null || $n === '') {
        return '—';
    }
    $s = rtrim(rtrim(number_format((float) $n, 2, ',', ''), '0'), ',');
    return $s === '' ? '0' : $s;
}

// Giá trị điền vào ô số: 85.50 -> "85.5"; null -> ""
function score_input($n)
{
    if ($n === null || $n === '') {
        return '';
    }
    $s = rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
    return $s === '' ? '0' : $s;
}

function badge($text, $color)
{
    return '<span class="badge text-bg-' . e($color) . '">' . e($text) . '</span>';
}

function eval_badge($status)
{
    return badge(EVAL_LABELS[$status] ?? $status, EVAL_COLORS[$status] ?? 'secondary');
}

function phase_badge($phase)
{
    return badge(PHASE_LABELS[$phase] ?? $phase, PHASE_COLORS[$phase] ?? 'secondary');
}
