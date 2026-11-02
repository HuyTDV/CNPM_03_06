<?php // Thông báo flash: dùng flash('success'|'danger'|'warning'|'info', 'nội dung') ở controller. ?>
<?php foreach (flash_pull() as $__f): ?>
    <div class="alert alert-<?= e($__f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($__f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
    </div>
<?php endforeach; ?>
