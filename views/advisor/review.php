<?php // Danh sách sinh viên của lớp để GVCN duyệt. Cần: $class, $period, $phase, $list, $summary. ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1">Duyệt điểm lớp <?= e($class['code']) ?></h1>
        <div class="text-muted"><?= e($period['name']) ?> · <?= phase_badge($phase) ?> · Hạn GVCN duyệt: <?= e(fmt_datetime($period['advisor_end_at'])) ?></div>
    </div>
    <a href="<?= e(url('advisor/dashboard')) ?>" class="btn btn-outline-secondary">Về tổng quan</a>
</div>

<div class="stat-grid mb-3">
    <div class="stat-card"><div class="stat-num"><?= $summary['total'] ?></div><div class="stat-label">Sinh viên</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['not_started'] + $summary['draft'] ?></div><div class="stat-label">Chưa nộp</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['submitted'] ?></div><div class="stat-label">Chờ lớp chấm</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['class_graded'] ?></div><div class="stat-label">Chờ duyệt</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['need_edit'] ?></div><div class="stat-label">Cần chỉnh sửa</div></div>
    <div class="stat-card"><div class="stat-num"><?= $summary['approved'] ?></div><div class="stat-label">Đã duyệt</div></div>
</div>

<form method="post" action="<?= e(url('advisor/approve-bulk/' . $class['id'] . '/' . $period['id'])) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="text-muted small">Chọn các bạn đã kiểm tra xong rồi bấm "Duyệt đã chọn": hệ thống lấy điểm lớp chấm làm điểm duyệt.</div>
        <button type="submit" class="btn btn-brand px-3" id="bulkBtn"
                data-confirm="Duyệt các sinh viên đã chọn, lấy điểm lớp chấm làm điểm chính thức?">Duyệt đã chọn (<span id="pickCount">0</span>)</button>
    </div>
    <div class="table-card">
        <table class="table table-hover align-middle mb-0">
            <thead>
            <tr><th style="width:2.5rem"><input type="checkbox" class="form-check-input" id="pickAll" aria-label="Chọn tất cả"></th>
                <th>MSSV</th><th>Họ và tên</th><th>Trạng thái</th><th class="text-center">Tự chấm</th><th class="text-center">Lớp chấm</th>
                <th class="text-center">Chính thức</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($list as $r): ?>
                <tr>
                    <td><?php if ($r['can_review']): ?><input type="checkbox" class="form-check-input pick" name="ids[]" value="<?= (int) $r['evaluation_id'] ?>"><?php endif; ?></td>
                    <td class="fw-semibold"><?= e($r['student_code']) ?></td>
                    <td><?= e($r['full_name']) ?><?php if ($r['is_monitor']): ?> <span class="soon-badge">Cán bộ lớp</span><?php endif; ?></td>
                    <td><?= $r['evaluation_id'] ? eval_badge($r['status']) : '<span class="text-muted">Chưa bắt đầu</span>' ?></td>
                    <td class="text-center"><?= e(fmt_score($r['self_total'])) ?></td>
                    <td class="text-center"><?= e(fmt_score($r['class_total'])) ?></td>
                    <td class="text-center">
                        <?php if (in_array($r['status'], EVAL_RESULT_VISIBLE, true)): ?><strong><?= e(fmt_score($r['final_total'])) ?></strong> <small><?= e($r['rating']) ?></small>
                        <?php else: ?>—<?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php if ($r['can_review']): ?>
                            <a class="btn btn-brand btn-sm px-3" href="<?= e(url('advisor/student/' . $r['evaluation_id'])) ?>">Xem và duyệt</a>
                        <?php elseif ($r['evaluation_id'] && $r['status'] !== EVAL_DRAFT): ?>
                            <a class="btn btn-outline-secondary btn-sm px-3" href="<?= e(url('advisor/student/' . $r['evaluation_id'])) ?>">Xem</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>
