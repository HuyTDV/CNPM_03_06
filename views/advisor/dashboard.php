<section class="welcome-card">
    <h1 class="h3 mb-1">Xin chào, <?= e(Auth::user()['full_name']) ?> 👋</h1>
    <p class="mb-0 text-muted">Các lớp bạn phụ trách và tiến độ duyệt điểm rèn luyện.</p>
</section>

<h2 class="h5 mt-4 mb-3">Tiến độ theo lớp và đợt</h2>
<?php if (!$rows): ?>
    <div class="alert alert-info">Bạn chưa phụ trách lớp nào hoặc chưa có đợt đánh giá nào.</div>
<?php else: ?>
    <div class="table-card">
        <table class="table align-middle mb-0">
            <thead>
            <tr><th>Lớp</th><th>Đợt đánh giá</th><th>Giai đoạn</th><th class="text-center">Chưa nộp</th><th class="text-center">Chờ lớp chấm</th>
                <th class="text-center">Chờ duyệt</th><th class="text-center">Cần sửa</th><th class="text-center">Đã duyệt</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): $s = $r['summary']; $p = $r['period']; ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($r['class']['code']) ?></div><small class="text-muted"><?= $s['total'] ?> sinh viên</small></td>
                    <td><?= e($p['name']) ?><div class="small text-muted">Hạn duyệt: <?= e(fmt_datetime($p['advisor_end_at'])) ?></div></td>
                    <td><?= phase_badge($p['phase']) ?></td>
                    <td class="text-center"><?= $s['not_started'] + $s['draft'] ?></td>
                    <td class="text-center"><?= $s['submitted'] ?></td>
                    <td class="text-center fw-semibold"><?= $s['class_graded'] ?></td>
                    <td class="text-center"><?= $s['need_edit'] ?></td>
                    <td class="text-center"><?= $s['approved'] ?></td>
                    <td class="text-end"><a class="btn btn-brand btn-sm px-3" href="<?= e(url('advisor/review/' . $r['class']['id'] . '/' . $p['id'])) ?>">Duyệt điểm</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
