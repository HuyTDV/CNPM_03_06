<section class="welcome-card">
    <h1 class="h3 mb-1">Xin chào, <?= e(Auth::user()['full_name']) ?> 👋</h1>
    <p class="mb-0 text-muted">Cán bộ lớp <strong><?= e($class['code']) ?></strong> · <?= e($class['name']) ?> · <?= e($class['faculty_name']) ?></p>
</section>

<h2 class="h5 mt-4 mb-3">Tiến độ chấm điểm của lớp</h2>
<?php if (!$rows): ?>
    <div class="alert alert-info">Chưa có đợt đánh giá nào.</div>
<?php else: ?>
    <div class="table-card">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Đợt đánh giá</th><th>Giai đoạn</th><th class="text-center">Chưa nộp</th><th class="text-center">Chờ lớp chấm</th>
                <th class="text-center">Đã gửi GVCN</th><th class="text-center">Cần sửa</th><th class="text-center">Đã duyệt</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): $s = $r['summary']; $p = $r['period']; ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($p['name']) ?></div><small class="text-muted">Hạn lớp chấm: <?= e(fmt_datetime($p['class_end_at'])) ?></small></td>
                    <td><?= phase_badge($p['phase']) ?></td>
                    <td class="text-center"><?= $s['not_started'] + $s['draft'] ?></td>
                    <td class="text-center fw-semibold"><?= $s['submitted'] ?></td>
                    <td class="text-center"><?= $s['class_graded'] ?></td>
                    <td class="text-center"><?= $s['need_edit'] ?></td>
                    <td class="text-center"><?= $s['approved'] ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-brand btn-sm px-3" href="<?= e(url('class/students/' . $p['id'])) ?>">Chấm điểm</a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('minute/show/' . $p['id'])) ?>">Biên bản</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
