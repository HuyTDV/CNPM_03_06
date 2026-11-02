<?php
// GVCN xem chi tiết một sinh viên và duyệt / yêu cầu chỉnh sửa. Cần: $ev, $period, $canEdit, $tree, $scores, $evidences, $history, $old.
$level = 'advisor';
$totalMax = score_sum(array_column($tree, 'max_score'));
$note = $old['note'] ?? ($ev['advisor_note'] ?? '');
$levelNames = ['class' => 'Lớp', 'advisor' => 'GVCN', 'school' => 'Nhà trường'];
$actionNames = ['submit' => 'đã gửi lên', 'approve' => 'đã duyệt', 'request_edit' => 'yêu cầu chỉnh sửa'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1"><?= e($ev['full_name']) ?> <small class="text-muted">(<?= e($ev['student_code']) ?>)</small></h1>
        <div class="text-muted"><?= e($period['name']) ?> · Lớp <?= e($ev['class_code']) ?></div>
    </div>
    <div class="text-end">
        <?= eval_badge($ev['status']) ?>
        <?php if (in_array($ev['status'], EVAL_RESULT_VISIBLE, true)): ?>
            <div class="mt-1"><strong><?= e(fmt_score($ev['final_total'])) ?></strong> điểm · <?= e($ev['rating']) ?></div>
        <?php endif; ?>
        <div class="mt-2"><a href="<?= e(url('advisor/review/' . $ev['class_id'] . '/' . $period['id'])) ?>" class="btn btn-outline-secondary btn-sm">Về danh sách</a></div>
    </div>
</div>

<?php if (!$canEdit): ?>
    <div class="alert alert-info">
        <?php if (in_array($ev['status'], EVAL_RESULT_VISIBLE, true)): ?>Bài này đã được duyệt.
        <?php elseif ($ev['status'] === EVAL_NEED_EDIT): ?>Đang chờ sinh viên chỉnh sửa theo yêu cầu của bạn.
        <?php elseif ($ev['status'] === EVAL_SUBMITTED): ?>Bài đang chờ lớp chấm điểm, chưa đến lượt GVCN.
        <?php elseif ($ev['status'] === EVAL_DRAFT): ?>Sinh viên chưa nộp bài.
        <?php else: ?>Đã hết hạn GVCN duyệt hoặc đợt đã bị khóa.<?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($ev['self_note']): ?><div class="alert alert-light border"><strong>Ghi chú của sinh viên:</strong> <?= e($ev['self_note']) ?></div><?php endif; ?>
<?php if ($ev['class_note']): ?><div class="alert alert-light border"><strong>Nhận xét của lớp:</strong> <?= e($ev['class_note']) ?></div><?php endif; ?>

<form id="reviewForm" method="post" action="<?= e(url('advisor/approve/' . $ev['id'])) ?>">
    <?= csrf_field() ?>
    <?php require BASE_PATH . '/views/partials/review-table.php'; ?>

    <?php if ($canEdit): ?>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label for="advisor_note" class="form-label">Nhận xét khi duyệt (không bắt buộc)</label>
                <textarea class="form-control" id="advisor_note" name="advisor_note" rows="3" maxlength="1000"><?= e($note) ?></textarea>
            </div>
            <div class="col-6">
                <label for="reason" class="form-label">Lý do yêu cầu chỉnh sửa <span class="text-muted">(bắt buộc nếu bấm "Yêu cầu chỉnh sửa")</span></label>
                <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="1000" placeholder="Ví dụ: Mục 3.1 thiếu minh chứng, em bổ sung rồi nộp lại."></textarea>
            </div>
        </div>
    <?php endif; ?>

    <div class="total-bar">
        <div>Tổng điểm GVCN: <strong class="fs-5" id="grandTotal">0</strong> / <?= e(fmt_score($totalMax)) ?>
            <span class="text-muted small ms-2">(mặc định lấy điểm lớp chấm)</span></div>
        <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <button type="submit" formaction="<?= e(url('advisor/request-edit/' . $ev['id'])) ?>" formnovalidate class="btn btn-outline-warning"
                        data-confirm="Trả bài về cho sinh viên chỉnh sửa theo lý do bạn đã nhập?">Yêu cầu chỉnh sửa</button>
                <button type="submit" class="btn btn-brand px-4"
                        data-confirm="Duyệt bài với điểm GVCN ở trên? Đây sẽ là điểm chính thức.">Duyệt</button>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($history): ?>
    <h2 class="h6 mt-4 mb-2">Lịch sử xử lý</h2>
    <div class="card-block">
        <ul class="list-group list-group-flush">
            <?php foreach ($history as $h): ?>
                <li class="list-group-item small">
                    <strong><?= e($h['actor_name'] ?? 'Không rõ') ?></strong>
                    (<?= e($levelNames[$h['level']] ?? $h['level']) ?>) <?= e($actionNames[$h['action']] ?? $h['action']) ?>
                    <span class="text-muted">· <?= e(fmt_datetime($h['created_at'])) ?></span>
                    <?php if ($h['reason']): ?><div class="text-muted">Lý do: <?= e($h['reason']) ?></div><?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
