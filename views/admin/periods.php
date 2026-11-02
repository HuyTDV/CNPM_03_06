<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Đợt đánh giá</h1>
    <a href="<?= e(url('period/create')) ?>" class="btn btn-brand px-3">+ Tạo đợt đánh giá</a>
</div>

<?php if (!$periods): ?>
    <div class="alert alert-info">Chưa có đợt đánh giá nào.</div>
<?php else: ?>
    <div class="table-card">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr><th style="min-width:16rem">Đợt</th><th style="min-width:11rem">Khung điểm</th><th>Bắt đầu</th><th>SV nộp</th><th>Lớp chấm</th><th>GVCN duyệt</th><th>Trạng thái</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($periods as $p): ?>
                <tr>
                    <td><div class="fw-semibold"><?= e($p['name']) ?></div><small class="text-muted"><?= e($p['semester_name']) ?></small></td>
                    <td class="small"><?= e($p['framework_name']) ?></td>
                    <td class="small text-nowrap"><?= e(fmt_datetime($p['start_at'])) ?></td>
                    <td class="small text-nowrap"><?= e(fmt_datetime($p['student_end_at'])) ?></td>
                    <td class="small text-nowrap"><?= e(fmt_datetime($p['class_end_at'])) ?></td>
                    <td class="small text-nowrap"><?= e(fmt_datetime($p['advisor_end_at'])) ?></td>
                    <td><?= phase_badge($p['phase']) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('period/edit/' . $p['id'])) ?>">Sửa</a>
                        <form method="post" class="d-inline" action="<?= e(url(($p['status'] === PERIOD_OPEN ? 'period/lock/' : 'period/unlock/') . $p['id'])) ?>">
                            <?= csrf_field() ?>
                            <?php if ($p['status'] === PERIOD_OPEN): ?>
                                <button class="btn btn-sm btn-outline-warning" data-confirm="Khóa đợt này? Sinh viên sẽ không nộp hay sửa bài được nữa.">Khóa</button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-success">Mở khóa</button>
                            <?php endif; ?>
                        </form>
                        <?php if (!$p['has_evaluations']): ?>
                            <form method="post" class="d-inline" action="<?= e(url('period/delete/' . $p['id'])) ?>">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa đợt &quot;<?= e($p['name']) ?>&quot;?">Xóa</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
