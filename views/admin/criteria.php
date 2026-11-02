<?php
// Trang khung điểm. Cần: $frameworks, $selected, $tree, $locked, $problems, $categorySum, $semesters.
// Các nút Thêm/Sửa mở hộp thoại trong views/admin/criteria-modals.php; public/js/criteria.js điền sẵn dữ liệu từ thuộc tính data-*.
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Khung điểm & tiêu chí</h1>
    <button type="button" class="btn btn-brand px-3" data-bs-toggle="modal" data-bs-target="#frameworkModal"
            data-modal-title="Thêm khung điểm" data-total-max-score="100">+ Thêm khung điểm</button>
</div>

<div class="row g-4">
    <div class="col-3">
        <div class="list-group">
            <?php foreach ($frameworks as $f): ?>
                <a href="<?= e(url('criteria?framework=' . $f['id'])) ?>"
                   class="list-group-item list-group-item-action <?= $selected && $selected['id'] == $f['id'] ? 'active' : '' ?>">
                    <div class="fw-semibold"><?= e($f['name']) ?></div>
                    <small><?= e($f['semester_name']) ?></small>
                    <?php if ($f['is_active']): ?><span class="badge text-bg-success ms-1">Đang bật</span><?php endif; ?>
                </a>
            <?php endforeach; ?>
            <?php if (!$frameworks): ?><div class="list-group-item text-muted">Chưa có khung điểm.</div><?php endif; ?>
        </div>
    </div>

    <div class="col-9">
    <?php if (!$selected): ?>
        <div class="alert alert-info">Hãy thêm khung điểm đầu tiên.</div>
    <?php else: ?>
        <div class="card-block mb-3">
            <div class="p-3 d-flex justify-content-between gap-3">
                <div>
                    <h2 class="h5 mb-1"><?= e($selected['name']) ?>
                        <?= $selected['is_active'] ? '<span class="badge text-bg-success">Đang bật</span>' : '<span class="badge text-bg-secondary">Đang tắt</span>' ?></h2>
                    <div class="text-muted small"><?= e($selected['semester_name']) ?> · Tổng tối đa <?= e(fmt_score($selected['total_max_score'])) ?> điểm</div>
                    <?php if ($selected['description']): ?><div class="small mt-1"><?= e($selected['description']) ?></div><?php endif; ?>
                </div>
                <div class="d-flex gap-1 align-items-start flex-shrink-0">
                    <?php if (!$locked): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#frameworkModal"
                                data-modal-title="Sửa khung điểm" data-id="<?= (int) $selected['id'] ?>" data-name="<?= e($selected['name']) ?>"
                                data-description="<?= e($selected['description']) ?>" data-semester-id="<?= (int) $selected['semester_id'] ?>"
                                data-total-max-score="<?= e(score_input($selected['total_max_score'])) ?>">Sửa</button>
                    <?php endif; ?>
                    <form method="post" action="<?= e(url('criteria/toggle-framework')) ?>" class="m-0">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                        <button class="btn btn-sm <?= $selected['is_active'] ? 'btn-outline-warning' : 'btn-success' ?>"><?= $selected['is_active'] ? 'Tắt khung' : 'Bật khung' ?></button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#copyModal"
                            data-id="<?= (int) $selected['id'] ?>" data-name="<?= e($selected['name']) ?> (bản sao)">Sao chép</button>
                    <form method="post" action="<?= e(url('criteria/delete-framework')) ?>" class="m-0">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa khung điểm &quot;<?= e($selected['name']) ?>&quot; cùng toàn bộ điều và khoản?">Xóa</button>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($locked): ?>
            <div class="alert alert-warning">Khung này đã có sinh viên đánh giá nên không thể sửa điểm, điều và khoản.</div>
        <?php endif; ?>
        <?php if ($problems): ?>
            <div class="alert alert-warning">
                <strong>Khung điểm chưa hợp lệ, chưa thể bật:</strong>
                <ul class="mb-0"><?php foreach ($problems as $problem): ?><li><?= e($problem) ?></li><?php endforeach; ?></ul>
            </div>
        <?php else: ?>
            <div class="alert alert-success py-2">Khung điểm hợp lệ (tổng các điều = <?= e(fmt_score($categorySum)) ?> điểm).</div>
        <?php endif; ?>

        <?php foreach ($tree as $cat): ?>
            <?php $itemSum = score_sum(array_column($cat['items'], 'max_score')); ?>
            <div class="card-block mb-3">
                <div class="card-block-head">
                    <div>
                        <strong>Điều <?= e($cat['code']) ?>.</strong> <?= e($cat['title']) ?>
                        <span class="badge text-bg-primary ms-1">Tối đa <?= e(fmt_score($cat['max_score'])) ?></span>
                        <span class="badge <?= abs($itemSum - $cat['max_score']) < 0.001 ? 'text-bg-success' : 'text-bg-warning' ?>">Các khoản: <?= e(fmt_score($itemSum)) ?></span>
                    </div>
                    <?php if (!$locked): ?>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#categoryModal"
                                    data-modal-title="Sửa điều" data-id="<?= (int) $cat['id'] ?>" data-framework-id="<?= (int) $selected['id'] ?>"
                                    data-code="<?= e($cat['code']) ?>" data-title="<?= e($cat['title']) ?>" data-description="<?= e($cat['description']) ?>"
                                    data-max-score="<?= e(score_input($cat['max_score'])) ?>">Sửa</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#itemModal"
                                    data-modal-title="Thêm khoản vào điều <?= e($cat['code']) ?>" data-category-id="<?= (int) $cat['id'] ?>"
                                    data-code="<?= e($cat['code']) ?>." data-min-score="0">+ Khoản</button>
                            <form method="post" action="<?= e(url('criteria/delete-category')) ?>" class="m-0">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa điều <?= e($cat['code']) ?> và các khoản bên trong?">Xóa</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if ($cat['items']): ?>
                    <table class="table align-middle mb-0 score-table">
                        <thead><tr><th style="width:5rem">Khoản</th><th>Nội dung</th><th class="text-center" style="width:8rem">Điểm</th><th style="width:9rem"></th></tr></thead>
                        <tbody>
                        <?php foreach ($cat['items'] as $it): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($it['code']) ?></td>
                                <td><?= e($it['content']) ?><?php if ($it['require_evidence']): ?><span class="soon-badge">Cần minh chứng</span><?php endif; ?></td>
                                <td class="text-center"><?= e(fmt_score($it['min_score'])) ?> – <?= e(fmt_score($it['max_score'])) ?></td>
                                <td class="text-end text-nowrap">
                                    <?php if (!$locked): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#itemModal"
                                                data-modal-title="Sửa khoản" data-id="<?= (int) $it['id'] ?>" data-category-id="<?= (int) $cat['id'] ?>"
                                                data-code="<?= e($it['code']) ?>" data-content="<?= e($it['content']) ?>"
                                                data-max-score="<?= e(score_input($it['max_score'])) ?>" data-min-score="<?= e(score_input($it['min_score'])) ?>"
                                                data-require-evidence="<?= (int) $it['require_evidence'] ?>">Sửa</button>
                                        <form method="post" action="<?= e(url('criteria/delete-item')) ?>" class="d-inline">
                                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $it['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa khoản <?= e($it['code']) ?>?">Xóa</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="p-3 text-muted small">Điều này chưa có khoản nào.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if (!$locked): ?>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#categoryModal"
                    data-modal-title="Thêm điều" data-framework-id="<?= (int) $selected['id'] ?>">+ Thêm điều</button>
        <?php endif; ?>
    <?php endif; ?>
    </div>
</div>

<?php require BASE_PATH . '/views/admin/criteria-modals.php'; ?>
