<?php
// Cán bộ lớp xem minh chứng và chấm điểm một sinh viên. Cần: $ev, $period, $isSelf, $canEdit, $tree, $scores, $evidences, $old.
$level = 'class';
$totalMax = score_sum(array_column($tree, 'max_score'));
$note = $old['note'] ?? ($ev['class_note'] ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1">Chấm điểm: <?= e($ev['full_name']) ?> <small class="text-muted">(<?= e($ev['student_code']) ?>)</small></h1>
        <div class="text-muted"><?= e($period['name']) ?> · Lớp <?= e($ev['class_code']) ?></div>
    </div>
    <div class="text-end">
        <?= eval_badge($ev['status']) ?>
        <div class="mt-2"><a href="<?= e(url('class/students/' . $period['id'])) ?>" class="btn btn-outline-secondary btn-sm">Về danh sách</a></div>
    </div>
</div>

<?php if ($isSelf): ?>
    <div class="alert alert-info">Đây là bài của chính bạn nên bạn không tự chấm được. GVCN sẽ xem xét trực tiếp.</div>
<?php elseif (!$canEdit): ?>
    <div class="alert alert-info">
        <?php if ($ev['status'] === EVAL_SUBMITTED): ?>Đã hết hạn chấm của lớp hoặc đợt đã bị khóa.
        <?php elseif ($ev['status'] === EVAL_DRAFT): ?>Sinh viên chưa nộp bài.
        <?php else: ?>Bài này đã được gửi lên GVCN (hoặc đang chờ sinh viên sửa) nên không chỉnh được. Bạn chỉ có thể xem.<?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($ev['self_note']): ?>
    <div class="alert alert-light border"><strong>Ghi chú của sinh viên:</strong> <?= e($ev['self_note']) ?></div>
<?php endif; ?>

<form id="reviewForm" method="post" action="<?= e(url('class/save-scores/' . $ev['id'])) ?>">
    <?= csrf_field() ?>
    <?php require BASE_PATH . '/views/partials/review-table.php'; ?>

    <div class="mb-3">
        <label for="class_note" class="form-label">Nhận xét chung của lớp (không bắt buộc)</label>
        <textarea class="form-control" id="class_note" name="class_note" rows="2" maxlength="1000" <?= $canEdit ? '' : 'disabled' ?>><?= e($note) ?></textarea>
    </div>
    <div class="total-bar">
        <div>Tổng điểm lớp chấm: <strong class="fs-5" id="grandTotal">0</strong> / <?= e(fmt_score($totalMax)) ?>
            <span class="text-muted small ms-2">(ô nhập mặc định lấy điểm tự chấm, sửa nếu lớp thống nhất điểm khác)</span></div>
        <?php if ($canEdit): ?><button type="submit" class="btn btn-brand px-4">Lưu điểm lớp</button><?php endif; ?>
    </div>
</form>
