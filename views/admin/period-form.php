<?php
// Form tạo / sửa đợt. Cần: $period (null khi tạo mới), $locked, $semesters, $frameworks.
$old = $_SESSION['old_period'] ?? [];          // dữ liệu lần gửi trước (khi có lỗi), dùng một lần
unset($_SESSION['old_period']);
$edit = $period !== null;
$defaults = [
    'start_at'       => date('Y-m-d H:i:00'),
    'student_end_at' => date('Y-m-d 23:59:00', strtotime('+14 days')),
    'class_end_at'   => date('Y-m-d 23:59:00', strtotime('+21 days')),
    'advisor_end_at' => date('Y-m-d 23:59:00', strtotime('+28 days')),
];
// Giá trị một ô: ưu tiên dữ liệu vừa gõ, rồi dữ liệu đang lưu, rồi giá trị mặc định
$val = function ($key) use ($old, $period, $defaults) {
    return isset($old[$key]) && is_string($old[$key]) ? $old[$key] : (string) ($period[$key] ?? $defaults[$key] ?? '');
};
$selSemester = (int) ($old['semester_id'] ?? $period['semester_id'] ?? 0);
$selFramework = (int) ($old['framework_id'] ?? $period['framework_id'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0"><?= $edit ? 'Sửa đợt đánh giá' : 'Tạo đợt đánh giá' ?></h1>
    <a href="<?= e(url('period')) ?>" class="btn btn-outline-secondary">Quay lại</a>
</div>

<?php if ($locked): ?>
    <div class="alert alert-warning">Đợt đã có sinh viên đánh giá nên không đổi được học kỳ và khung điểm. Bạn vẫn sửa được tên, ghi chú và các mốc thời gian.</div>
<?php endif; ?>

<form method="post" id="periodForm" class="card-block p-4" style="max-width: 46rem"
      action="<?= e(url($edit ? 'period/update/' . $period['id'] : 'period/store')) ?>">
    <?= csrf_field() ?>
    <div class="mb-3">
        <label class="form-label" for="name">Tên đợt</label>
        <input type="text" class="form-control" id="name" name="name" maxlength="150" required
               value="<?= e($val('name')) ?>" placeholder="Đánh giá rèn luyện HK1 2026-2027">
    </div>
    <div class="row">
        <div class="col-6 mb-3">
            <label class="form-label" for="semester_id">Học kỳ</label>
            <select class="form-select" id="semester_id" name="semester_id" <?= $locked ? 'disabled' : 'required' ?>>
                <option value="">— Chọn học kỳ —</option>
                <?php foreach ($semesters as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= $selSemester === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 mb-3">
            <label class="form-label" for="framework_id">Khung điểm áp dụng</label>
            <select class="form-select" id="framework_id" name="framework_id" <?= $locked ? 'disabled' : 'required' ?>>
                <option value="">— Chọn khung điểm —</option>
                <?php foreach ($frameworks as $f): ?>
                    <option value="<?= (int) $f['id'] ?>" data-semester="<?= (int) $f['semester_id'] ?>"
                            <?= $selFramework === (int) $f['id'] ? 'selected' : '' ?>><?= e($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Chỉ hiện khung đang bật của học kỳ đã chọn.</div>
        </div>
    </div>

    <h2 class="h6 mt-2">Các mốc thời gian</h2>
    <div class="row">
        <?php foreach (['start_at' => '1. Bắt đầu đợt', 'student_end_at' => '2. Hạn sinh viên nộp',
                        'class_end_at' => '3. Hạn lớp chấm', 'advisor_end_at' => '4. Hạn GVCN duyệt'] as $key => $label): ?>
            <div class="col-6 mb-3">
                <label class="form-label" for="<?= $key ?>"><?= e($label) ?></label>
                <input type="datetime-local" class="form-control" id="<?= $key ?>" name="<?= $key ?>" required
                       value="<?= e(datetime_input($val($key))) ?>">
            </div>
        <?php endforeach; ?>
    </div>
    <div class="form-text mb-3">Thứ tự bắt buộc: 1 ≤ 2 ≤ 3 ≤ 4. Quá mốc 4 đợt sẽ tự khóa.</div>

    <div class="mb-3">
        <label class="form-label" for="note">Ghi chú</label>
        <textarea class="form-control" id="note" name="note" rows="2" maxlength="500"><?= e($val('note')) ?></textarea>
    </div>
    <button type="submit" class="btn btn-brand px-4"><?= $edit ? 'Lưu thay đổi' : 'Tạo đợt' ?></button>
</form>
