<div class="error-box">
    <div class="error-code">404</div>
    <h1 class="h4">Không tìm thấy trang</h1>
    <p class="text-muted">Địa chỉ không tồn tại hoặc đã bị đổi.</p>
    <a class="btn btn-brand" href="<?= e(url(Auth::check() ? Auth::homeFor(Auth::role()) : 'auth/login')) ?>">Về trang chủ</a>
</div>
