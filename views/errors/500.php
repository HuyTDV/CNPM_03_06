<div class="error-box">
    <div class="error-code">500</div>
    <h1 class="h4">Hệ thống gặp sự cố</h1>
    <p class="text-muted">Vui lòng thử lại sau. Nếu lỗi lặp lại, hãy báo cho người quản trị.</p>
    <?php if (!empty($detail)): ?>
        <div class="alert alert-warning text-start small">
            <strong>Chi tiết lỗi (chỉ hiện khi debug = true):</strong><br><?= e($detail) ?>
        </div>
    <?php endif; ?>
    <a class="btn btn-brand" href="<?= e(url('')) ?>">Về trang chủ</a>
</div>
