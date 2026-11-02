<?php
// Bảng điểm xem xét, dùng chung cho cán bộ lớp (US-05) và GVCN (US-06).
// Cần: $tree, $scores, $evidences, $level ('class' hoặc 'advisor'), $canEdit, $old.
//  - class  : hiện điểm tự chấm, cho NHẬP điểm lớp.
//  - advisor: hiện điểm tự chấm và điểm lớp, cho NHẬP điểm GVCN.
// Ô nhập mặc định lấy điểm của cấp trước (tự chấm / lớp chấm) để người duyệt chỉ sửa chỗ khác.
$isClass = $level === 'class';
$oldScores = $old['scores'] ?? [];
$oldNotes = $old['notes'] ?? [];
?>
<?php foreach ($tree as $cat): ?>
    <div class="card-block score-category" data-max="<?= e($cat['max_score']) ?>">
        <div class="card-block-head">
            <div><strong>Điều <?= e($cat['code']) ?>.</strong> <?= e($cat['title']) ?></div>
            <div class="text-nowrap">Tổng: <strong class="cat-sum">0</strong> / <?= e(fmt_score($cat['max_score'])) ?></div>
        </div>
        <table class="table align-middle mb-0 score-table">
            <thead>
            <tr>
                <th style="width:4.5rem">Khoản</th><th>Nội dung</th><th class="text-center" style="width:4.5rem">Tối đa</th>
                <th class="text-center" style="width:6rem">Tự chấm</th>
                <?php if (!$isClass): ?><th class="text-center" style="width:6rem">Lớp chấm</th><?php endif; ?>
                <th style="width:8rem"><?= $isClass ? 'Điểm lớp' : 'Điểm GVCN' ?></th>
                <th style="width:12rem">Ghi chú</th><th style="width:14rem">Minh chứng</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($cat['items'] as $it): ?>
                <?php
                $id = (int) $it['id'];
                $row = $scores[$id] ?? [];
                $default = $isClass ? ($row['class_score'] ?? $row['self_score'] ?? null)
                                    : ($row['advisor_score'] ?? $row['class_score'] ?? $row['self_score'] ?? null);
                $val = isset($oldScores[$id]) && is_string($oldScores[$id]) ? $oldScores[$id] : score_input($default);
                $ownNote = $isClass ? ($row['class_note'] ?? '') : ($row['advisor_note'] ?? '');
                $note = isset($oldNotes[$id]) && is_string($oldNotes[$id]) ? $oldNotes[$id] : (string) $ownNote;
                $files = $evidences[$id] ?? [];
                ?>
                <tr>
                    <td class="fw-semibold"><?= e($it['code']) ?></td>
                    <td><?= e($it['content']) ?></td>
                    <td class="text-center"><?= e(fmt_score($it['max_score'])) ?></td>
                    <td class="text-center">
                        <strong><?= e(fmt_score($row['self_score'] ?? null)) ?></strong>
                        <?php if (!empty($row['self_note'])): ?><div class="small text-muted" title="Giải trình của sinh viên">“<?= e($row['self_note']) ?>”</div><?php endif; ?>
                    </td>
                    <?php if (!$isClass): ?>
                        <td class="text-center"><strong><?= e(fmt_score($row['class_score'] ?? null)) ?></strong>
                            <?php if (!empty($row['class_note'])): ?><div class="small text-muted">“<?= e($row['class_note']) ?>”</div><?php endif; ?></td>
                    <?php endif; ?>
                    <td>
                        <input type="number" step="0.1" min="<?= e($it['min_score']) ?>" max="<?= e($it['max_score']) ?>"
                               class="form-control form-control-sm score-input" name="scores[<?= $id ?>]" value="<?= e($val) ?>"
                               <?= $canEdit ? '' : 'disabled' ?> aria-label="Điểm khoản <?= e($it['code']) ?>">
                    </td>
                    <td><input type="text" maxlength="500" class="form-control form-control-sm" name="notes[<?= $id ?>]"
                               value="<?= e($note) ?>" <?= $canEdit ? '' : 'disabled' ?> aria-label="Ghi chú khoản <?= e($it['code']) ?>"></td>
                    <td>
                        <?php foreach ($files as $f): ?>
                            <div class="file-line">
                                <a href="<?= e(url('evidence/view/' . $f['id'])) ?>" target="_blank" rel="noopener"
                                   title="<?= e($f['original_name']) ?>"><?= e($f['original_name']) ?></a>
                                <span class="text-muted">(<?= e(round($f['file_size'] / 1024)) ?> KB)</span>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!$files && $it['require_evidence']): ?><span class="small text-danger fw-semibold">Thiếu minh chứng</span>
                        <?php elseif (!$files): ?><span class="small text-muted">Không có</span><?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>
