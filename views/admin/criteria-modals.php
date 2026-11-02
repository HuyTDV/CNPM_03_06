<?php
// Các hộp thoại thêm/sửa. public/js/criteria.js điền sẵn từ thuộc tính data-* của nút bấm:
// tên input dạng snake_case khớp với data-* dạng camelCase (framework_id <-> data-framework-id).
?>
<!-- Khung điểm -->
<div class="modal fade" id="frameworkModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post" action="<?= e(url('criteria/save-framework')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id">
        <div class="modal-header"><h2 class="modal-title fs-5" data-modal-title>Khung điểm</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Tên khung</label><input class="form-control" name="name" maxlength="150" required></div>
            <div class="mb-3"><label class="form-label">Học kỳ áp dụng</label>
                <select class="form-select" name="semester_id" required>
                    <option value="">— Chọn —</option>
                    <?php foreach ($semesters as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select></div>
            <div class="mb-3"><label class="form-label">Tổng điểm tối đa</label><input class="form-control" name="total_max_score" type="number" step="0.5" min="1" max="1000" required></div>
            <div class="mb-0"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="2" maxlength="500"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-brand px-4">Lưu</button></div>
    </form></div>
</div>

<!-- Sao chép khung -->
<div class="modal fade" id="copyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><form class="modal-content" method="post" action="<?= e(url('criteria/copy-framework')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id">
        <div class="modal-header"><h2 class="modal-title fs-5">Sao chép khung điểm</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label class="form-label">Tên khung mới</label><input class="form-control" name="name" maxlength="150" required></div>
            <div class="mb-0"><label class="form-label">Sang học kỳ</label>
                <select class="form-select" name="semester_id" required>
                    <option value="">— Chọn —</option>
                    <?php foreach ($semesters as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?>
                </select>
                <div class="form-text">Khung mới ở trạng thái tắt; kiểm tra rồi bật khi sẵn sàng.</div></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-brand px-4">Sao chép</button></div>
    </form></div>
</div>

<!-- Điều -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="<?= e(url('criteria/save-category')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id"><input type="hidden" name="framework_id">
        <div class="modal-header"><h2 class="modal-title fs-5" data-modal-title>Điều</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-4 mb-3"><label class="form-label">Mã điều</label><input class="form-control" name="code" maxlength="10" required></div>
                <div class="col-8 mb-3"><label class="form-label">Điểm tối đa của điều</label><input class="form-control" name="max_score" type="number" step="0.5" min="0" required></div>
            </div>
            <div class="mb-3"><label class="form-label">Tiêu đề</label><input class="form-control" name="title" maxlength="255" required></div>
            <div class="mb-0"><label class="form-label">Mô tả</label><textarea class="form-control" name="description" rows="2" maxlength="500"></textarea></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-brand px-4">Lưu</button></div>
    </form></div>
</div>

<!-- Khoản -->
<div class="modal fade" id="itemModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg"><form class="modal-content" method="post" action="<?= e(url('criteria/save-item')) ?>">
        <?= csrf_field() ?><input type="hidden" name="id"><input type="hidden" name="category_id">
        <div class="modal-header"><h2 class="modal-title fs-5" data-modal-title>Khoản</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
        <div class="modal-body">
            <div class="row">
                <div class="col-4 mb-3"><label class="form-label">Mã khoản</label><input class="form-control" name="code" maxlength="15" required></div>
                <div class="col-4 mb-3"><label class="form-label">Điểm tối đa</label><input class="form-control" name="max_score" type="number" step="0.5" min="0" required></div>
                <div class="col-4 mb-3"><label class="form-label">Điểm tối thiểu</label><input class="form-control" name="min_score" type="number" step="0.5">
                    <div class="form-text">Để âm nếu là mục trừ điểm.</div></div>
            </div>
            <div class="mb-3"><label class="form-label">Nội dung tiêu chí</label><textarea class="form-control" name="content" rows="2" maxlength="500" required></textarea></div>
            <div class="form-check"><input class="form-check-input" type="checkbox" name="require_evidence" id="requireEvidence" value="1">
                <label class="form-check-label" for="requireEvidence">Bắt buộc đính kèm minh chứng</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-brand px-4">Lưu</button></div>
    </form></div>
</div>
