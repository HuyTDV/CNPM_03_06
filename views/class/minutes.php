<?php
// Form biên bản họp lớp. Cần: $class, $period, $minute, $old, $user, $total.
$val = function ($key, $default = '') use ($old, $minute) {
    return isset($old[$key]) && is_string($old[$key]) ? $old[$key] : (string) ($minute[$key] ?? $default);
};
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h1 class="h4 mb-1">Biên bản họp lớp <?= e($class['code']) ?></h1>
        <div class="text-muted"><?= e($period['name']) ?></div>
    </div>
    <div class="d-flex gap-2">
        <?php if ($minute): ?>
            <a class="btn btn-outline-secondary" href="<?= e(url('minute/preview/' . $period['id'])) ?>" target="_blank">Xem bản in</a>
            <a class="btn btn-outline-primary" href="<?= e(url('minute/word/' . $period['id'])) ?>">Tải file Word</a>
        <?php endif; ?>
        <a class="btn btn-outline-secondary" href="<?= e(url('class/students/' . $period['id'])) ?>">Về danh sách</a>
    </div>
</div>
<?php if (!$minute): ?><div class="alert alert-info">Chưa có biên bản. Điền thông tin và lưu để xem bản in hoặc tải file Word.</div><?php endif; ?>

<form method="post" action="<?= e(url('minute/save/' . $period['id'])) ?>" class="card-block p-4" style="max-width: 52rem">
    <?= csrf_field() ?>
    <div class="row">
        <div class="col-6 mb-3"><label class="form-label" for="meeting_at">Thời gian họp</label>
            <input type="datetime-local" class="form-control" id="meeting_at" name="meeting_at" required
                   value="<?= e(datetime_input($val('meeting_at', date('Y-m-d 19:00:00')))) ?>"></div>
        <div class="col-6 mb-3"><label class="form-label" for="venue">Địa điểm</label>
            <input class="form-control" id="venue" name="venue" maxlength="200" required value="<?= e($val('venue')) ?>" placeholder="Phòng A2-301"></div>
        <div class="col-6 mb-3"><label class="form-label" for="chairperson">Chủ trì</label>
            <input class="form-control" id="chairperson" name="chairperson" maxlength="100" required value="<?= e($val('chairperson', $user['full_name'])) ?>"></div>
        <div class="col-6 mb-3"><label class="form-label" for="secretary">Thư ký</label>
            <input class="form-control" id="secretary" name="secretary" maxlength="100" required value="<?= e($val('secretary')) ?>"></div>
        <div class="col-6 mb-3"><label class="form-label" for="present_count">Số sinh viên có mặt</label>
            <input type="number" min="0" class="form-control" id="present_count" name="present_count" required value="<?= e($val('present_count', $total)) ?>"></div>
        <div class="col-6 mb-3"><label class="form-label" for="total_count">Tổng số sinh viên của lớp</label>
            <input type="number" min="0" class="form-control" id="total_count" name="total_count" required value="<?= e($val('total_count', $total)) ?>"></div>
    </div>
    <div class="mb-3"><label class="form-label" for="content">Nội dung cuộc họp</label>
        <textarea class="form-control" id="content" name="content" rows="5" maxlength="5000" required
                  placeholder="Lớp thông qua bảng tự đánh giá của từng sinh viên, đối chiếu minh chứng và thống nhất điểm đề xuất..."><?= e($val('content')) ?></textarea></div>
    <div class="mb-3"><label class="form-label" for="conclusion">Kết luận</label>
        <textarea class="form-control" id="conclusion" name="conclusion" rows="3" maxlength="2000"><?= e($val('conclusion')) ?></textarea></div>
    <button type="submit" class="btn btn-brand px-4">Lưu biên bản</button>
</form>
