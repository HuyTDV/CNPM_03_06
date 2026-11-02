<?php
// Tài liệu biên bản: dùng cho cả bản xem/in và file Word (Word mở được HTML có đuôi .doc).
// Đứng riêng, không có menu. Cần: $class, $period, $minute, $list, $forWord.
?>
<!DOCTYPE html>
<html lang="vi" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
    <meta charset="utf-8">
    <title>Biên bản họp lớp <?= e($class['code']) ?></title>
    <style>
        body { font-family: "Times New Roman", serif; font-size: 14pt; line-height: 1.5; margin: 2cm; color: #000; }
        h1 { text-align: center; font-size: 16pt; margin: 0.4cm 0 0.2cm; }
        .center { text-align: center; }
        table.list { border-collapse: collapse; width: 100%; margin: 0.4cm 0; font-size: 12pt; }
        table.list th, table.list td { border: 1px solid #000; padding: 4px 6px; }
        table.list th { background: #eee; }
        .sign { width: 100%; margin-top: 1cm; }
        .sign td { text-align: center; width: 50%; vertical-align: top; }
        .no-print { text-align: right; margin-bottom: 1cm; }
        @media print { .no-print { display: none; } body { margin: 1.5cm; } }
    </style>
</head>
<body>
<?php if (!$forWord): ?>
    <div class="no-print"><button onclick="window.print()" style="padding:6px 16px">In hoặc lưu PDF</button></div>
<?php endif; ?>
    <p class="center"><strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br>Độc lập - Tự do - Hạnh phúc</p>
    <h1>BIÊN BẢN HỌP LỚP<br>XÉT ĐIỂM RÈN LUYỆN</h1>
    <p class="center"><?= e($period['name']) ?></p>

    <p><strong>Lớp:</strong> <?= e($class['code']) ?> - <?= e($class['name']) ?> (<?= e($class['faculty_name']) ?>)<br>
       <strong>Thời gian:</strong> <?= e(fmt_datetime($minute['meeting_at'])) ?><br>
       <strong>Địa điểm:</strong> <?= e($minute['venue']) ?><br>
       <strong>Chủ trì:</strong> <?= e($minute['chairperson']) ?> &nbsp;&nbsp; <strong>Thư ký:</strong> <?= e($minute['secretary']) ?><br>
       <strong>Có mặt:</strong> <?= (int) $minute['present_count'] ?>/<?= (int) $minute['total_count'] ?> sinh viên</p>

    <p><strong>I. Nội dung cuộc họp</strong></p>
    <p><?= nl2br(e($minute['content'])) ?></p>

    <p><strong>II. Điểm rèn luyện đề xuất của lớp</strong></p>
    <table class="list">
        <tr><th>STT</th><th>MSSV</th><th>Họ và tên</th><th>Điểm tự chấm</th><th>Điểm lớp chấm</th></tr>
        <?php foreach ($list as $i => $r): ?>
            <tr><td class="center"><?= $i + 1 ?></td><td><?= e($r['student_code']) ?></td><td><?= e($r['full_name']) ?></td>
                <td class="center"><?= e(fmt_score($r['self_total'])) ?></td><td class="center"><?= e(fmt_score($r['class_total'])) ?></td></tr>
        <?php endforeach; ?>
    </table>

    <?php if ($minute['conclusion']): ?>
        <p><strong>III. Kết luận</strong></p>
        <p><?= nl2br(e($minute['conclusion'])) ?></p>
    <?php endif; ?>

    <table class="sign"><tr>
        <td><strong>Thư ký</strong><br><em>(Ký, ghi rõ họ tên)</em><br><br><br><?= e($minute['secretary']) ?></td>
        <td><strong>Chủ trì</strong><br><em>(Ký, ghi rõ họ tên)</em><br><br><br><?= e($minute['chairperson']) ?></td>
    </tr></table>
</body>
</html>
