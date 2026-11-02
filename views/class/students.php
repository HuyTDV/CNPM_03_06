<?php // Danh sách sinh viên của lớp trong một đợt. Cần: $class, $period, $phase, $list, $summary. ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1">Chấm điểm lớp <?= e($class['code']) ?></h1>
        <div class="text-muted"><?= e($period['name']) ?> · <?= phase_badge($phase) ?> · Hạn lớp chấm: <?= e(fmt_datetime($period['class_end_at'])) ?></div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary" href="<?= e(url('minute/show/' . $period['id'])) ?>">Biên bản họp lớp</a>
        <form method="post" action="<?= e(url('class/send/' . $period['id'])) ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="btn btn-brand px-3" data-confirm="Gửi GVCN tất cả bài đã nộp và đã chấm đủ điểm lớp? Sau khi gửi bạn không sửa điểm lớp được nữa.">Gửi GVCN</button>
        </form>
    </div>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card"><div class="stat-num"><?= $summary['total'] ?></div><div class="stat-label">Sinh viên</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['not_started'] + $summary['draft'] ?></div><div class="stat-label">Chưa nộp</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['submitted'] ?></div><div class="stat-label">Chờ lớp chấm</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['class_graded'] ?></div><div class="stat-label">Đã gửi GVCN</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['need_edit'] ?></div><div class="stat-label">Cần chỉnh sửa</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['approved'] ?></div><div class="stat-label">Đã duyệt</div></div>
</div>

<div class="table-card">
    <table class="table table-hover align-middle mb-0">
        <thead>
        <tr><th>MSSV</th><th>Họ và tên</th><th>Trạng thái</th><th class="text-center">Tự chấm</th><th class="text-center">Lớp chấm</th><th class="text-center">Chính thức</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($list as $r): ?>
            <tr>
                <td class="fw-semibold"><?= e($r['student_code']) ?></td>
                <td><?= e($r['full_name']) ?><?php if ($r['is_self']): ?> <span class="soon-badge">Bạn</span><?php endif; ?></td>
                <td><?= $r['evaluation_id'] ? eval_badge($r['status']) : '<span class="text-muted">Chưa bắt đầu</span>' ?></td>
                <td class="text-center"><?= e(fmt_score($r['self_total'])) ?></td>
                <td class="text-center"><?= e(fmt_score($r['class_total'])) ?></td>
                <td class="text-center">
                    <?php if (in_array($r['status'], EVAL_RESULT_VISIBLE, true)): ?><strong><?= e(fmt_score($r['final_total'])) ?></strong> <small><?= e($r['rating']) ?></small>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td class="text-end">
                    <?php if ($r['can_grade']): ?>
                        <a class="btn btn-brand btn-sm px-3" href="<?= e(url('class/grade/' . $r['evaluation_id'])) ?>">Chấm điểm</a>
                    <?php elseif ($r['evaluation_id'] && $r['status'] !== EVAL_DRAFT): ?>
                        <a class="btn btn-outline-secondary btn-sm px-3" href="<?= e(url('class/grade/' . $r['evaluation_id'])) ?>">Xem</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<p class="text-muted small mt-3">Bài của chính cán bộ lớp do GVCN xem xét trực tiếp. "Gửi GVCN" chỉ gửi các bài đã nộp và đã chấm đủ mọi khoản.</p>
