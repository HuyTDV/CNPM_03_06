<?php
// Form tự đánh giá. Cần: $period, $phase, $eval, $canEdit, $tree, $scores, $evidences, $old.
$totalMax = score_sum(array_column($tree, 'max_score'));
$oldScores = $old['scores'] ?? [];
$oldNotes = $old['notes'] ?? [];
$selfNote = $old['self_note'] ?? ($eval['self_note'] ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1">Tự đánh giá rèn luyện</h1>
        <div class="text-muted"><?= e($period['name']) ?> · <?= e($period['semester_name']) ?></div>
    </div>
    <div class="text-end">
        <?= eval_badge($eval['status']) ?> <?= phase_badge($phase) ?>
        <div class="small text-muted mt-1">
            Hạn nộp: <?= e(fmt_datetime($period['student_end_at'])) ?>
            <?php if ($eval['status'] === EVAL_NEED_EDIT): ?> · Hạn sửa lại: <?= e(fmt_datetime($period['advisor_end_at'])) ?><?php endif; ?>
        </div>
    </div>
</div>

<?php if ($eval['status'] === EVAL_NEED_EDIT && $eval['advisor_note']): ?>
    <div class="alert alert-warning"><strong>GVCN yêu cầu chỉnh sửa:</strong><br><?= nl2br(e($eval['advisor_note'])) ?></div>
<?php endif; ?>
<?php if (!$canEdit): ?>
    <div class="alert alert-info">
        <?php if ($period['status'] === PERIOD_LOCKED): ?>Đợt đánh giá đã bị khóa.
        <?php elseif ($eval['status'] === EVAL_DRAFT): ?>Đã quá hạn nộp bài của đợt này.
        <?php else: ?>Bài đánh giá đã được gửi nên không thể chỉnh sửa. Bạn chỉ có thể xem lại.<?php endif; ?>
    </div>
<?php endif; ?>

<form id="assessmentForm" method="post" action="<?= e(url('assessment/save/' . $period['id'])) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <?php foreach ($tree as $cat): ?>
        <div class="card-block score-category" data-max="<?= e($cat['max_score']) ?>">
            <div class="card-block-head">
                <div><strong>Điều <?= e($cat['code']) ?>.</strong> <?= e($cat['title']) ?></div>
                <div class="text-nowrap">Tổng: <strong class="cat-sum">0</strong> / <?= e(fmt_score($cat['max_score'])) ?></div>
            </div>
            <table class="table align-middle mb-0 score-table">
                <thead>
                <tr><th style="width:4.5rem">Khoản</th><th>Nội dung</th><th class="text-center" style="width:5.5rem">Tối đa</th>
                    <th style="width:8rem">Điểm tự chấm</th><th style="width:14rem">Giải trình</th><th style="width:18rem">Minh chứng</th></tr>
                </thead>
                <tbody>
                <?php foreach ($cat['items'] as $it): ?>
                    <?php
                    $id = (int) $it['id'];
                    $val = isset($oldScores[$id]) && is_string($oldScores[$id]) ? $oldScores[$id] : score_input($scores[$id]['self_score'] ?? null);
                    $note = isset($oldNotes[$id]) && is_string($oldNotes[$id]) ? $oldNotes[$id] : (string) ($scores[$id]['self_note'] ?? '');
                    $files = $evidences[$id] ?? [];
                    ?>
                    <tr>
                        <td class="fw-semibold"><?= e($it['code']) ?></td>
                        <td><?= e($it['content']) ?>
                            <?php if ($it['require_evidence']): ?><span class="soon-badge">Cần minh chứng</span><?php endif; ?></td>
                        <td class="text-center">
                            <?= e(fmt_score($it['max_score'])) ?>
                            <?php if ($it['min_score'] < 0): ?><div class="small text-muted">(từ <?= e(fmt_score($it['min_score'])) ?>)</div><?php endif; ?>
                        </td>
                        <td>
                            <input type="number" step="0.1" min="<?= e($it['min_score']) ?>" max="<?= e($it['max_score']) ?>"
                                   class="form-control form-control-sm score-input" name="scores[<?= $id ?>]"
                                   value="<?= e($val) ?>" <?= $canEdit ? '' : 'disabled' ?> aria-label="Điểm khoản <?= e($it['code']) ?>">
                        </td>
                        <td>
                            <input type="text" maxlength="500" class="form-control form-control-sm" name="notes[<?= $id ?>]"
                                   value="<?= e($note) ?>" <?= $canEdit ? '' : 'disabled' ?> aria-label="Giải trình khoản <?= e($it['code']) ?>">
                        </td>
                        <td>
                            <?php foreach ($files as $f): ?>
                                <div class="file-line">
                                    <a href="<?= e(url('evidence/view/' . $f['id'])) ?>" target="_blank" rel="noopener"
                                       title="<?= e($f['original_name']) ?>"><?= e($f['original_name']) ?></a>
                                    <span class="text-muted">(<?= e(round($f['file_size'] / 1024)) ?> KB)</span>
                                    <?php if ($canEdit): ?>
                                        <button type="submit" form="delform-<?= (int) $f['id'] ?>" class="btn btn-link btn-sm text-danger p-0"
                                                data-confirm="Xóa file &quot;<?= e($f['original_name']) ?>&quot;?">Xóa</button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                            <?php if ($canEdit): ?>
                                <input type="file" class="form-control form-control-sm upload-input mt-1" name="evidence[<?= $id ?>][]"
                                       multiple accept=".pdf,.jpg,.jpeg,.png"
                                       data-remaining="<?= max(0, MAX_EVIDENCE_PER_ITEM - count($files)) ?>" data-max-size="<?= MAX_UPLOAD_BYTES ?>">
                                <div class="form-text upload-msg text-danger"></div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

    <div class="mb-3">
        <label for="self_note" class="form-label">Ghi chú chung (không bắt buộc)</label>
        <textarea class="form-control" id="self_note" name="self_note" rows="2" maxlength="1000" <?= $canEdit ? '' : 'disabled' ?>><?= e($selfNote) ?></textarea>
    </div>

    <div class="total-bar">
        <div>Tổng điểm tự chấm: <strong class="fs-5" id="grandTotal">0</strong> / <?= e(fmt_score($totalMax)) ?></div>
        <?php if ($canEdit): ?>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn btn-outline-secondary">Lưu nháp</button>
                <button type="submit" name="action" value="submit" class="btn btn-brand px-4"
                        data-confirm="Sau khi gửi bài bạn không thể sửa nữa (trừ khi GVCN yêu cầu chỉnh sửa). Bạn chắc chắn muốn gửi?">Gửi bài</button>
            </div>
        <?php endif; ?>
    </div>
</form>

<?php if ($canEdit): ?>
    <?php // Form xóa minh chứng đặt NGOÀI form chính (HTML không cho lồng form); nút Xóa trỏ tới bằng thuộc tính form="" ?>
    <?php foreach ($evidences as $files): foreach ($files as $f): ?>
        <form id="delform-<?= (int) $f['id'] ?>" method="post" action="<?= e(url('evidence/delete/' . $f['id'])) ?>" class="d-none"><?= csrf_field() ?></form>
    <?php endforeach; endforeach; ?>
<?php endif; ?>
